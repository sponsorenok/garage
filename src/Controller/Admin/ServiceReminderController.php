<?php

namespace App\Controller\Admin;

use App\Repository\ServiceEventRepository;
use App\Repository\ServicePlanRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ServiceReminderController extends AbstractController
{
    #[Route('/admin/service-reminders', name: 'admin_service_reminders')]
    public function reminders(
        ServicePlanRepository $plansRepo,
        ServiceEventRepository $serviceEventRepo,
        AdminUrlGenerator $adminUrlGenerator,
    ): Response {
        $today = new \DateTimeImmutable('today');

        $plans = $plansRepo->findActiveWithVehicleAndTasks();

        // зібрати всі planTask IDs
        $planTaskIds = [];


        foreach ($plans as $plan) {
            foreach ($plan->getTasks() as $pt) {
                if ($pt->getId()) $planTaskIds[] = $pt->getId();
            }
        }

        $lastDoneMap = $plansRepo->findLastDoneByPlanTaskIds($planTaskIds);
        $planIds = array_map(fn($p) => $p->getId(), $plans);

        $lastPlanEvents = $plansRepo->findLastEventsByPlanIds(array_filter($planIds));
        $openEvents = $serviceEventRepo->findOpenEventsByPlanIds(array_filter($planIds));

        $rows = [];

        foreach ($plans as $plan) {
            $vehicle = $plan->getVehicle();
            if (!$vehicle) continue;

            $vehicleOdo = $vehicle->getOdometerKm();
            $tasks = $plan->getTasks();

            // ✅ Якщо tasks є — task-рівень
            $added = false;
            $openEvent = $openEvents[$plan->getId()] ?? null;
            if ($openEvent) {
                $p = $openEvent->getTasksProgress();

                if ($p['isAllDone']) {
                    $rows[] = [
                        'plan' => $plan,
                        'vehicle' => $vehicle,
                        'taskName' => $plan->getName().' (готово '.$p['text'].')',
                        'status' => 'READY_TO_CLOSE',
                        'reasons' => [[
                            'label' => 'Всі задачі виконані. Закрий ТО.',
                        ]],
                        // щоб було високо у списку
                        'priority' => -1,
                        // опційно: щоб у Twig можна було зробити кнопку "Відкрити ТО"
                        'openEvent' => $openEvent,
                        'openEventUrl' => $adminUrlGenerator
                            ->setController(\App\Controller\Admin\ServiceEventCrudController::class)
                            ->setAction('edit')
                            ->setEntityId($openEvent->getId())
                            ->generateUrl(),
                    ];
                    continue;
                }

                $rows[] = [
                    'plan' => $plan,
                    'vehicle' => $vehicle,
                    'taskName' => $plan->getName().' (в процесі '.$p['text'].')',
                    'status' => 'IN_PROGRESS',
                    'reasons' => [[
                        'label' => 'Є відкрите ТО, задачі виконуються: '.$p['text'],
                    ]],
                    'priority' => 500000.0,
                    'openEvent' => $openEvent,
                    'openEventUrl' => $adminUrlGenerator
                        ->setController(\App\Controller\Admin\ServiceEventCrudController::class)
                        ->setAction('edit')
                        ->setEntityId($openEvent->getId())
                        ->generateUrl(),
                ];
                continue;
            }



            if (!$tasks->isEmpty()) {
                foreach ($tasks as $pt) {$lastDone = $lastDoneMap[$pt->getId()] ?? null;

// база (doneDate може бути null якщо ти ще не мігрував/не проставив — тоді fallback)
                    $baseDate = $lastDone?->getDoneDate()
                        ?? ($lastPlanEvents[$plan->getId()] ?? null)?->getServiceDate()
                        ?? $today;

                    $baseOdo = $lastDone?->getDoneOdometerKm()
                        ?? ($lastPlanEvents[$plan->getId()] ?? null)?->getOdometerKm()
                        ?? null;

                    $intervalDays = $pt->getIntervalDays();
                    $intervalKm   = $pt->getIntervalKm();

                    $soonDays = $pt->getSoonDays() ?? $plan->getSoonDays();
                    $soonKm   = $pt->getSoonKm() ?? $plan->getSoonKm();

// якщо задача взагалі не має правил — пропускаємо
                    if ($intervalDays === null && $intervalKm === null) {
                        continue;
                    }

// рахуємо dueAt/daysLeft
                    $dueAt = null;
                    $daysLeft = null;
                    if ($intervalDays !== null) {
                        $dueAt = \DateTimeImmutable::createFromInterface($baseDate)->modify("+{$intervalDays} days")->setTime(0,0);
                        $daysLeft = (int)$today->diff($dueAt)->format('%r%a');
                    }

// рахуємо dueKm/kmLeft
                    $dueKm = null;
                    $kmLeft = null;
                    if ($intervalKm !== null && $baseOdo !== null) {
                        $dueKm = $baseOdo + $intervalKm;
                        $kmLeft = $dueKm - $vehicleOdo;
                    }

// статуси
                    $overdue = ($kmLeft !== null && $kmLeft <= 0) || ($daysLeft !== null && $daysLeft <= 0);
                    $dueSoon = !$overdue && (
                            ($kmLeft !== null && $kmLeft <= $soonKm) ||
                            ($daysLeft !== null && $daysLeft <= $soonDays)
                        );

                    if (!$overdue && !$dueSoon) {
                        continue;
                    }

// reasons
                    $reasons = [];
                    if ($dueKm !== null) {
                        $reasons[] = ['label' => 'По пробігу (задача)', 'dueKm' => $dueKm, 'left' => $kmLeft];
                    }
                    if ($dueAt !== null) {
                        $reasons[] = ['label' => 'По даті (задача)', 'dueAt' => $dueAt, 'left' => $daysLeft];
                    }

// priority: беремо найменший normalized left
                    $priority = 999999.0;
                    if ($kmLeft !== null)   $priority = min($priority, $kmLeft / max(1, $soonKm));
                    if ($daysLeft !== null) $priority = min($priority, $daysLeft / max(1, $soonDays));

                    $rows[] = [
                        'plan' => $plan,
                        'vehicle' => $vehicle,
                        'task' => $pt,
                        'taskName' => $pt->getName(),
                        'status' => $overdue ? 'OVERDUE' : 'DUE_SOON',
                        'reasons' => $reasons,
                        'priority' => $priority,
                    ];
                    $added = true;

                }

                // ✅ якщо задачі є, але нічого не додалось — НЕ ХОВАЄМО план
                if (!$added) {
                    $rows[] = [
                        'plan' => $plan,
                        'vehicle' => $vehicle,
                        'taskName' => $plan->getName().' (задачі є, але немає бази/не налаштовано)',
                        'status' => 'NO_BASE',
                        'reasons' => [[ 'label' => 'Є задачі в плані, але немає даних для нагадування (нема виконання або не задані інтервали)' ]],
                        'priority' => 999999.0,
                    ];
                }

                continue;
            }


            // ✅ Fallback: якщо tasks немає — рахуємо як раніше по ServicePlan
            $intervalKm = $plan->getIntervalKm();
            $intervalDays = $plan->getIntervalDays();

            if ($intervalKm === null && $intervalDays === null && $plan->getAbsoluteDueOdometerKm() === null && $plan->getAbsoluteDueAt() === null) {
                continue;
            }

            // останній ServiceEvent по плану (якщо ти раніше мав мапу lastEventByPlanId — використовуй її)
            // Якщо вже прибрав — тимчасово ставимо NO_BASE
            $last = $lastPlanEvents[$plan->getId()] ?? null; // <- якщо маєш $lastEventsMap[$plan->getId()] ?? null — підстав

            // дедлайни
            $dueKmFromLast = null;
            $dueAtFromLast = null;

            if ($last && $intervalKm !== null && $last->getOdometerKm() !== null) {
                $dueKmFromLast = $last->getOdometerKm() + $intervalKm;
            }
            if ($last && $intervalDays !== null && $last->getServiceDate() instanceof \DateTimeInterface) {
                $base = \DateTimeImmutable::createFromInterface($last->getServiceDate());
                $dueAtFromLast = $base->modify('+'.$intervalDays.' days')->setTime(0, 0);
            }

            // абсолютні
            $dueKmAbs = $plan->getAbsoluteDueOdometerKm();
            $dueAtAbs = $plan->getAbsoluteDueAt() ? \DateTimeImmutable::createFromInterface($plan->getAbsoluteDueAt()) : null;

            // left
            $kmLeftCandidates = [];
            if ($dueKmFromLast !== null) $kmLeftCandidates[] = $dueKmFromLast - $vehicleOdo;
            if ($dueKmAbs !== null)      $kmLeftCandidates[] = $dueKmAbs - $vehicleOdo;
            $kmLeft = $kmLeftCandidates ? min($kmLeftCandidates) : null;

            $daysLeftCandidates = [];
            if ($dueAtFromLast !== null) $daysLeftCandidates[] = (int)$today->diff($dueAtFromLast)->format('%r%a');
            if ($dueAtAbs !== null)      $daysLeftCandidates[] = (int)$today->diff($dueAtAbs)->format('%r%a');
            $daysLeft = $daysLeftCandidates ? min($daysLeftCandidates) : null;

            // якщо немає last — показуємо як NO_BASE
            if (!$last) {
                $rows[] = [
                    'plan' => $plan,
                    'vehicle' => $vehicle,
                    'taskName' => $plan->getName().' (без задач)',
                    'status' => 'NO_BASE',
                    'reasons' => [[ 'label' => 'Немає виконаного ТО для плану (створи перший запис ТО)' ]],
                    'priority' => 999999.0,
                ];
                continue;
            }

            // статус
            $overdue = ($kmLeft !== null && $kmLeft <= 0) || ($daysLeft !== null && $daysLeft <= 0);
            $dueSoon = !$overdue && (
                    ($kmLeft !== null && $kmLeft <= $plan->getSoonKm()) ||
                    ($daysLeft !== null && $daysLeft <= $plan->getSoonDays())
                );

            if (!$overdue && !$dueSoon) continue;

            // reasons
            $reasons = [];
            if ($dueKmFromLast !== null) {
                $left = $dueKmFromLast - $vehicleOdo;
                if ($overdue ? $left <= 0 : $left <= $plan->getSoonKm()) {
                    $reasons[] = ['label' => 'По пробігу від останнього ТО', 'dueKm' => $dueKmFromLast, 'left' => $left];
                }
            }
            if ($dueKmAbs !== null) {
                $left = $dueKmAbs - $vehicleOdo;
                if ($overdue ? $left <= 0 : $left <= $plan->getSoonKm()) {
                    $reasons[] = ['label' => 'По загальному пробігу', 'dueKm' => $dueKmAbs, 'left' => $left];
                }
            }
            if ($dueAtFromLast !== null) {
                $left = (int)$today->diff($dueAtFromLast)->format('%r%a');
                if ($overdue ? $left <= 0 : $left <= $plan->getSoonDays()) {
                    $reasons[] = ['label' => 'По даті від останнього ТО', 'dueAt' => $dueAtFromLast, 'left' => $left];
                }
            }
            if ($dueAtAbs !== null) {
                $left = (int)$today->diff($dueAtAbs)->format('%r%a');
                if ($overdue ? $left <= 0 : $left <= $plan->getSoonDays()) {
                    $reasons[] = ['label' => 'По абсолютній даті', 'dueAt' => $dueAtAbs, 'left' => $left];
                }
            }

            // priority (як ти вже робив)
            $priority = 999999.0;
            foreach ($reasons as $reason) {
                if (isset($reason['dueKm'])) {
                    $priority = min($priority, $reason['left'] / max(1, $plan->getSoonKm()));
                } elseif (isset($reason['dueAt'])) {
                    $priority = min($priority, $reason['left'] / max(1, $plan->getSoonDays()));
                }
            }

            $rows[] = [
                'plan' => $plan,
                'vehicle' => $vehicle,
                'taskName' => $plan->getName().' (без задач)',
                'status' => $overdue ? 'OVERDUE' : 'DUE_SOON',
                'reasons' => $reasons,
                'priority' => $priority,
            ];
        }


        // сортування: OVERDUE -> DUE_SOON -> NO_BASE, і по priority
        usort($rows, function ($a, $b) {
            $rank = fn($s) => match ($s) {
                'READY_TO_CLOSE' => 0,
                'OVERDUE' => 1,
                'DUE_SOON' => 2,
                'IN_PROGRESS' => 3,
                'NO_BASE' => 4,
                default => 9,
            };


            $ra = $rank($a['status'] ?? '');
            $rb = $rank($b['status'] ?? '');

            if ($ra !== $rb) return $ra <=> $rb;

            $pa = $a['priority'] ?? 999999.0;
            $pb = $b['priority'] ?? 999999.0;
            if ($pa !== $pb) return $pa <=> $pb;

            // tie-breaker: vehicle then task id
            $va = $a['vehicle']->getId() ?? 0;
            $vb = $b['vehicle']->getId() ?? 0;
            if ($va !== $vb) return $va <=> $vb;

            $ta = $a['task']->getId() ?? 0;
            $tb = $b['task']->getId() ?? 0;
            return $ta <=> $tb;
        });

        return $this->render('admin/service_reminders.html.twig', [
            'rows' => $rows,
        ]);
    }
}
