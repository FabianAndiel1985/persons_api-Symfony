<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Person;

class MainController extends AbstractController
{
    #[Route('/main', name: 'app_main')]
    public function index(): JsonResponse
    {
        
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/MainController.php',
        ]);
    }

    #[Route('/add', name: 'add_person', methods: ['POST'])]
    public function add_person(Request $request, ManagerRegistry $doctrine ): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $content = $request->getContent();
        $content = json_decode($content, true);
        $person=$this->createPersonFromRequest($content);
        $entityManager->persist($person);
        $entityManager->flush();
 
        return $this->json([
            'message' => 'Content given out '.$person->getFirstname(),
            'path' => 'src/Controller/MainController.php',
        ]);
    }


    private function createPersonFromRequest($requestContent): Person {
        $person = new Person();
        $person->setFirstname($requestContent["firstname"]);
        $person->setLastname($requestContent["lastname"]);
        $person->setStreet($requestContent["street"]);
        $person->setHousenumber($requestContent["housenumber"]);
        $person->setAppartment($requestContent["appartment"]);
        $person->setPhonenumber($requestContent["phonenumber"]);

        return $person;
    }


}
