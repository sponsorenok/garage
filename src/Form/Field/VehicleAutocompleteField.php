<?php

namespace App\Form\Field;

use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

#[AsEntityAutocompleteField]
final class VehicleAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Vehicle::class,
            'required' => false,
            'placeholder' => '—',
            // Додатково можна стилі зменшити:
            'attr' => ['class' => 'form-select form-select-sm'],
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
