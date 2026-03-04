<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users');
    }

    public function configureFields(string $pageName): iterable
    {
        $roleChoices = [
            'Admin' => 'ROLE_ADMIN',
            'Manager' => 'ROLE_MANAGER',
            'Editor' => 'ROLE_EDITOR',
            // add your own roles here
        ];

        yield IdField::new('id')->hideOnForm();

        yield TextField::new('username');

        // Roles as checkboxes
        yield ChoiceField::new('roles')
            ->setLabel('Roles')
            ->setChoices($roleChoices)
            ->allowMultipleChoices()
            ->renderExpanded()
            ->setHelp('ROLE_USER is always granted automatically.');

        /**
         * Plain password field (NOT mapped to entity),
         * used only when you want to set/change password in admin.
         */
        yield TextField::new('plainPassword')
            ->setLabel('Password')
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions([
                'mapped' => false,
                'required' => false,
            ])
            ->onlyOnForms()
            ->setHelp('Leave empty to keep current password.');
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        $this->hashPasswordIfProvided($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        $this->hashPasswordIfProvided($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPasswordIfProvided(object $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $plainPassword = $this->getContext()?->getRequest()->request->all('User')['plainPassword'] ?? null;
        if (!is_string($plainPassword) || $plainPassword === '') {
            return;
        }

        $entityInstance->setPassword(
            $this->passwordHasher->hashPassword($entityInstance, $plainPassword)
        );
    }


}
