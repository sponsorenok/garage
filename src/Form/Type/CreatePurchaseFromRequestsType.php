<?php

namespace App\Form\Type;

use App\Entity\Supplier;
use App\Entity\Warehouse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

final class CreatePurchaseFromRequestsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('warehouse', EntityType::class, [
                'class' => Warehouse::class,
                'required' => true,
                'placeholder' => 'Оберіть склад',
                'label' => 'Склад',
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'required' => false,
                'placeholder' => '—',
                'label' => 'Постачальник',
                'choice_label' => fn(Supplier $s) => $s->getName() ?? 'Постачальник',
            ])
            ->add('purchaseDate', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'label' => 'Дата',
            ])
            ->add('currency', TextType::class, [
                'required' => true,
                'label' => 'Валюта',
                'data' => 'UAH',
                'help' => 'UAH / USD / EUR',
            ])
            ->add('invoiceNumber', TextType::class, [
                'required' => false,
                'label' => 'Номер накладної',
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'Нотатки',
            ]);
    }
}
