<?php

namespace App\Controller;

use App\Entity\Merchant;
use App\Entity\User;
use App\Entity\Role;
use App\Enum\MerchantStatus;
use App\Enum\KycLevel;
use App\Enum\PersonType;
use App\Repository\MerchantCategoryRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private MerchantCategoryRepository $categoryRepository,
        private RoleRepository $roleRepository
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('market_admin_dashboard');
        }

        $categories = $this->categoryRepository->findAll();

        if ($request->isMethod('POST')) {
            try {
                // Récupérer les données du formulaire
                $firstname = $request->request->get('firstname');
                $lastname = $request->request->get('lastname');
                $phone = $request->request->get('phone');
                $username = $request->request->get('username');
                $email = $request->request->get('email');
                $password = $request->request->get('password');
                $confirmPassword = $request->request->get('confirm_password');
                $categoryId = $request->request->get('category');
                $personType = $request->request->get('person_type', 'physical');

                // Validation
                if (!$firstname || !$lastname || !$phone || !$username || !$password) {
                    throw new \Exception('Tous les champs obligatoires doivent être remplis');
                }

                // Valider le format du nom d'utilisateur
                if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
                    throw new \Exception('Le nom d\'utilisateur doit contenir entre 3 et 20 caractères (lettres, chiffres et underscore uniquement)');
                }

                if ($password !== $confirmPassword) {
                    throw new \Exception('Les mots de passe ne correspondent pas');
                }

                if (strlen($password) < 6) {
                    throw new \Exception('Le mot de passe doit contenir au moins 6 caractères');
                }

                // Vérifier si le téléphone existe déjà
                $existingMerchant = $this->em->getRepository(Merchant::class)
                    ->findOneBy(['phone' => $phone]);
                
                if ($existingMerchant) {
                    throw new \Exception('Ce numéro de téléphone est déjà utilisé');
                }

                // Vérifier si le nom d'utilisateur existe déjà
                $existingUser = $this->em->getRepository(User::class)
                    ->findOneBy(['username' => $username]);
                
                if ($existingUser) {
                    throw new \Exception('Ce nom d\'utilisateur est déjà utilisé');
                }

                // Créer le marchand
                $merchant = new Merchant();
                $merchant->setFirstname($firstname);
                $merchant->setLastname($lastname);
                $merchant->setPhone($phone);
                $merchant->setEmail($email);
                $merchant->setStatus(MerchantStatus::PENDING_VALIDATION);
                $merchant->setKycLevel(KycLevel::BASIC);
                $merchant->setPersonType(PersonType::from($personType));
                
                $category = $this->categoryRepository->find(hex2bin($categoryId));
                if ($category) {
                    $merchant->setMerchantCategory($category);
                }

                // Créer l'utilisateur
                $user = new User();
                $user->setUsername($username); // Utiliser le nom d'utilisateur choisi
                
                // Définir l'email (obligatoire pour User)
                $userEmail = $email ?: $phone . '@zandomax.local';
                $user->setEmail($userEmail);
                
                // Ajouter le rôle MERCHANT
                $merchantRole = $this->roleRepository->findOneBy(['code' => 'ROLE_MERCHANT']);
                if ($merchantRole) {
                    $user->addUserRole($merchantRole);
                }
                
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                $user->setMerchant($merchant);

                $this->em->persist($merchant);
                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pourrez vous connecter avec votre nom d\'utilisateur après validation par l\'administrateur.');
                
                return $this->redirectToRoute('app_login');

            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('security/register.html.twig', [
            'categories' => $categories,
        ]);
    }
}
