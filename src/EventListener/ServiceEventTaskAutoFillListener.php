<?php

namespace App\EventListener;

use App\Entity\ServiceEvent;
use App\Entity\ServiceEventTask;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

final class ServiceEventTaskAutoFillListener
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->handle($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->handle($args->getObject());
    }

    private function handle(object $entity): void
    {
        if (!$entity instanceof ServiceEvent) {
            return;
        }

        $eventDate = $entity->getServiceDate(); // ?\DateTime
        $eventOdo  = $entity->getOdometerKm();  // int (у тебе не nullable)

        foreach ($entity->getTasks() as $task) {
            if (!$task instanceof ServiceEventTask) {
                continue;
            }

            if ($task->getStatus() === ServiceEventTask::STATUS_DONE) {
                if ($task->getDoneDate() === null && $eventDate !== null) {
                    $task->setDoneDate(clone $eventDate);
                }
                if ($task->getDoneOdometerKm() === null) {
                    $task->setDoneOdometerKm($eventOdo);
                }
            }
        }
    }
}
