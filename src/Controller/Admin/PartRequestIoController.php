<?php

namespace App\Controller\Admin;

use App\Entity\PartRequest;
use App\Entity\PartRequestItem;
use App\Enum\PartRequestCategory;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class PartRequestIoController extends AbstractController
{
    #[Route('/admin/part-request/{id}/export.xlsx', name: 'admin_part_request_export_xlsx')]
    public function exportXlsx(PartRequest $requestEntity): Response
    {
        $sheet = new Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->setTitle('Request');

        $ws->fromArray([['№', 'Найменування', 'Тип', 'Авто (plate)', 'К-сть', 'Коментар']], null, 'A1');

        $row = 2;
        foreach ($requestEntity->getItems() as $it) {
            $ws->fromArray([[
                $it->getLineNo(),
                $it->getNameRaw(),
                PartRequestCategory::label($it->getCategory()),
                $it->getVehicle()?->getPlate() ?? '',
                $it->getQty(),
                $it->getComment() ?? '',
            ]], null, 'A'.$row);
            $row++;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'req_') . '.xlsx';
        IOFactory::createWriter($sheet, 'Xlsx')->save($tmp);

        return (new BinaryFileResponse($tmp))
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'request_'.$requestEntity->getId().'.xlsx');
    }

    #[Route('/admin/part-request/{id}/export.csv', name: 'admin_part_request_export_csv')]
    public function exportCsv(PartRequest $requestEntity): Response
    {
        $lines = [];
        $lines[] = ['№','Найменування','Тип','Авто (plate)','К-сть','Коментар'];

        foreach ($requestEntity->getItems() as $it) {
            $lines[] = [
                (string)$it->getLineNo(),
                $it->getNameRaw(),
                PartRequestCategory::label($it->getCategory()),
                $it->getVehicle()?->getPlate() ?? '',
                (string)$it->getQty(),
                $it->getComment() ?? '',
            ];
        }

        $out = fopen('php://temp', 'r+');
        foreach ($lines as $l) {
            fputcsv($out, $l, ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="request_'.$requestEntity->getId().'.csv"',
        ]);
    }

    #[Route('/admin/part-request/{id}/template.xlsx', name: 'admin_part_request_template_xlsx')]
    public function templateXlsx(PartRequest $requestEntity): Response
    {
        $sheet = new Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->setTitle('Template');
        $ws->fromArray([['№', 'Найменування', 'Тип (value)', 'Авто (plate)', 'К-сть', 'Коментар']], null, 'A1');

        // Підказка по типам (value)
        $ws2 = $sheet->createSheet();
        $ws2->setTitle('Categories');
        $ws2->fromArray([['Label','Value']], null, 'A1');
        $r = 2;
        foreach (PartRequestCategory::choices() as $label => $value) {
            $ws2->fromArray([[$label, $value]], null, 'A'.$r);
            $r++;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'tpl_') . '.xlsx';
        IOFactory::createWriter($sheet, 'Xlsx')->save($tmp);

        return (new BinaryFileResponse($tmp))
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'request_template.xlsx');
    }

    #[Route('/admin/part-request/{id}/import', name: 'admin_part_request_import')]
    public function import(
        PartRequest $requestEntity,
        Request $request,
        EntityManagerInterface $em,
        VehicleRepository $vehicleRepo,
    ): Response {
        if ($request->isMethod('POST')) {
            /** @var UploadedFile|null $file */
            $file = $request->files->get('file');
            if (!$file) {
                $this->addFlash('danger', 'Файл не завантажено.');
                return $this->redirect($request->headers->get('referer') ?: '/admin');
            }

            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['xlsx', 'csv'], true)) {
                $this->addFlash('danger', 'Підтримуються лише XLSX або CSV.');
                return $this->redirect($request->headers->get('referer') ?: '/admin');
            }

            $rows = ($ext === 'xlsx')
                ? $this->readXlsx($file->getPathname())
                : $this->readCsv($file->getPathname());

            // Очистити поточні items чи додавати поверх? — я роблю ДОДАВАТИ, щоб не втратити руками внесене.
            // Якщо хочеш "replace" — скажи, дам перемикач.
            $maxLine = 0;
            foreach ($requestEntity->getItems() as $it) $maxLine = max($maxLine, $it->getLineNo());

            foreach ($rows as $r) {
                // очікуємо: №, name, category(value OR label), plate, qty, comment
                $lineNo = (int)($r[0] ?? 0);
                $name = trim((string)($r[1] ?? ''));
                if ($name === '') continue;

                $catRaw = trim((string)($r[2] ?? ''));
                $plate = trim((string)($r[3] ?? ''));
                $qty = (int)($r[4] ?? 1);
                $comment = trim((string)($r[5] ?? ''));

                $item = new PartRequestItem();
                $item->setRequest($requestEntity);

                // lineNo: якщо в файлі порожньо — пронумеруємо далі
                if ($lineNo <= 0) {
                    $maxLine++;
                    $item->setLineNo($maxLine);
                } else {
                    $item->setLineNo($lineNo);
                    $maxLine = max($maxLine, $lineNo);
                }

                $item->setNameRaw($name);
                $item->setQty($qty > 0 ? $qty : 1);
                $item->setComment($comment !== '' ? $comment : null);

                // category: приймаємо або Value (SUSPENSION), або Label (Підвіска)
                $catValue = $this->normalizeCategory($catRaw);
                $item->setCategory($catValue);

                // vehicle by plate
                if ($plate !== '') {
                    $vehicle = $vehicleRepo->findOneBy(['plate' => $plate]);
                    if ($vehicle) {
                        $item->setVehicle($vehicle);
                    }
                }

                $requestEntity->addItem($item);
                $em->persist($item);
            }

            $em->flush();
            $this->addFlash('success', 'Імпорт виконано.');

            // назад на detail заявки
            return $this->redirectToRoute('admin', [
                'crudControllerFqcn' => \App\Controller\Admin\PartRequestCrudController::class,
                'crudAction' => 'detail',
                'entityId' => $requestEntity->getId(),
            ]);
        }

        return $this->render('admin/part_request/import.html.twig', [
            'req' => $requestEntity,
        ]);
    }

    private function readXlsx(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $ws = $spreadsheet->getActiveSheet();
        $data = $ws->toArray(null, true, true, false);

        // прибираємо заголовок
        if (isset($data[0])) unset($data[0]);

        return array_values($data);
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        $h = fopen($path, 'r');
        if (!$h) return $rows;

        $first = true;
        while (($row = fgetcsv($h, 0, ';')) !== false) {
            if ($first) { $first = false; continue; } // header
            $rows[] = $row;
        }
        fclose($h);
        return $rows;
    }

    private function normalizeCategory(string $raw): ?string
    {
        if ($raw === '') return null;

        // якщо це вже value
        $values = array_values(PartRequestCategory::choices());
        if (in_array($raw, $values, true)) return $raw;

        // якщо це label
        $labelsToValues = [];
        foreach (PartRequestCategory::choices() as $label => $value) {
            $labelsToValues[mb_strtolower(trim($label))] = $value;
        }
        $key = mb_strtolower(trim($raw));
        return $labelsToValues[$key] ?? null;
    }
}
