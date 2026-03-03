<?php

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Entity\Item;
use App\Entity\PartRequest;
use App\Entity\ServiceEvent;
use App\Entity\ServiceEventItem;
use App\Entity\Supplier;
use App\Entity\Vehicle;
use App\Entity\VehicleType;
use App\Entity\Warehouse;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\InventoryBalanceRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Entity\Document;
use App\Entity\DocumentType;
use App\Controller\Admin\DocumentCrudController;
use App\Controller\Admin\DocumentTypeCrudController;
use App\Controller\Admin\PartRequestCrudController;
class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        /** @var \EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(\EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator::class);

        $url = $adminUrlGenerator
            ->setController(\App\Controller\Admin\PartRequestCrudController::class) // або PurchaseCrudController
            ->setAction(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Головна', 'fa fa-home');

        // This creates a sidebar link to Vehicle CRUD
        yield MenuItem::section('Довідники');
        yield MenuItem::linkToCrud('Автівки', 'fa fa-car', Vehicle::class);
        yield MenuItem::linkToCrud('Підрозділи', 'fa fa-sitemap', Department::class);
        yield MenuItem::linkToCrud('Зразки ОіВТ', 'fa fa-tags', VehicleType::class);
        yield MenuItem::linkToCrud('Постачальники', 'fa fa-truck', Supplier::class);
        yield MenuItem::linkToCrud('Підрозділи', 'fa fa-sitemap', Department::class);
        yield MenuItem::linkToCrud('Склади', 'fa fa-warehouse', \App\Entity\Warehouse::class);
        yield MenuItem::linkToCrud('Номенклатура', 'fa fa-cubes', \App\Entity\Item::class);
        yield MenuItem::linkToCrud('Документи', 'fa fa-file', Document::class);
        yield MenuItem::linkToCrud('Заявки на запчастини', 'fa fa-clipboard-list', PartRequest::class);
        yield MenuItem::linkToCrud('Типи документів', 'fa fa-tags', DocumentType::class);

        yield MenuItem::section('Обслуговування');
        yield MenuItem::linkToCrud('Шаблони планів', 'fa fa-copy', \App\Entity\ServicePlanTemplate::class);

        yield MenuItem::linkToCrud('ТО та ремонти', 'fa fa-wrench', \App\Entity\ServiceEvent::class);
        yield MenuItem::linkToRoute('Нагадування ТО', 'fa fa-bell', 'admin_service_reminders');
        yield MenuItem::linkToCrud('Плани ТО', 'fa fa-list', \App\Entity\ServicePlan::class);


        yield MenuItem::section('Склад');
        yield MenuItem::linkToCrud('Закупівлі', 'fa fa-cart-plus', \App\Entity\Purchase::class);
        yield MenuItem::linkToRoute('Залишки', 'fa fa-layer-group', 'admin_balances');
        yield MenuItem::linkToCrud('Рух складу', 'fa fa-exchange-alt', \App\Entity\InventoryTransaction::class);


    }

    #[Route('/admin/balances', name: 'admin_balances')]
    public function balances(InventoryBalanceRepository $repo): Response
        {
            $onlyMoved = true; // можна потім зробити перемикач
            $balances = $repo->fetchAll($onlyMoved);

            return $this->render('admin/balances.html.twig', [
                'balances' => $balances,
            ]);
        }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('assets/admin.css')
            ->addAssetMapperEntry('app')

            // CSS (напряму)
            ->addCssFile('https://cdn.jsdelivr.net/npm/jspreadsheet-ce@5.0.4/dist/jspreadsheet.css')
            ->addCssFile('https://cdn.jsdelivr.net/npm/jsuites@6.1.1/dist/jsuites.css');
    }

}

