<?php

namespace App\Form;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Vich\UploaderBundle\Form\Type\VichFileType;

final class DocumentUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['required' => false, 'label' => 'Назва'])
            ->add('docNumber', TextType::class, ['required' => false, 'label' => '№ документа'])
            ->add('docDate', DateType::class, ['required' => false, 'widget' => 'single_text', 'label' => 'Дата'])
            ->add('file', VichFileType::class, [
                'required' => true,
                'download_uri' => false,
                'allow_delete' => false,
                'label' => 'Файл (PDF/DOC/XLS)',
            ]);
    }
}

