<?php

namespace App\Form;

use App\Controller\Admin\VehicleCrudController;
use App\Entity\PartRequestItem;
use App\Entity\Vehicle;
use App\Enum\PartRequestCategory;
use App\Form\Field\VehicleAutocompleteField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
final class PartRequestItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lineNo', IntegerType::class, [
                'label' => '№',
                'attr' => ['style' => 'width:70px'],
            ])
            ->add('nameRaw', TextType::class, [
                'label' => 'Найменування',
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Тип',
                'required' => false,
                'choices' => PartRequestCategory::choices(),
                'placeholder' => '—',
                'attr' => ['style' => 'width:180px'],
            ])
            ->add('vehicle', VehicleAutocompleteField::class, [
                'label' => 'Авто',
            ])
            ->add('qty', IntegerType::class, [
                'label' => 'К-сть',
                'attr' => ['style' => 'width:90px'],
            ])
            ->add('comment', TextType::class, [
                'label' => 'Коментар',
                'required' => false,
            ]);
    }
}
