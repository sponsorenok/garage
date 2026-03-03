<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class PurchaseLinesSpreadsheetType extends AbstractType
{
    public function getParent(): string
    {
        return TextareaType::class; // ✅ тепер це звичайна textarea (рядок)
    }

    public function getBlockPrefix(): string
    {
        return 'purchase_lines_spreadsheet';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,

            // наші кастомні дані для Twig/JS
            'items_json' => '[]',
            'types_json' => '[]',
            'ajax_url' => '',
        ]);

        $resolver->setAllowedTypes('items_json', 'string');
        $resolver->setAllowedTypes('types_json', 'string');
        $resolver->setAllowedTypes('ajax_url', 'string');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // щоб у twig було form.vars.items_json / types_json / ajax_url
        $view->vars['items_json'] = $options['items_json'];
        $view->vars['types_json'] = $options['types_json'];
        $view->vars['ajax_url'] = $options['ajax_url'];
    }
}
