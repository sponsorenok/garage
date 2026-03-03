<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PartRequestItemsSpreadsheetType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,

            // JSON strings, підготовлені в CrudController
            'vehicles_json' => '[]',

            // [{label:"Двигун", value:"ENGINE"}, ...]
            'types_json' => '[]',
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['vehicles_json'] = (string) $options['vehicles_json'];
        $view->vars['types_json'] = (string) $options['types_json'];
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'part_request_items_spreadsheet';
    }
}
