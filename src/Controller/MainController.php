<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\PersonRepository;
use App\Entity\Person;
use Exception;

class MainController extends AbstractController
{
    
  
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
            'message' => 'Saved person in database: ',
             'firstname' => $person->getFirstname(),
             'lastname' => $person->getLastname(),
        ]);
    }


    #[Route('/get', name: 'get_person', methods: ['GET'])]
    public function get_person(Request $request,PersonRepository $personRepository, SerializerInterface $serializer): JsonResponse
    {
        $encoder = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoder);
        $content = $request->getContent();
        $content = json_decode($content, true);
        $id = $content["id"];
        $person= $personRepository->findOneBySomeField($id);
        $jsonContent = $serializer->serialize($person, 'json');

         return $this->json([
             'message' => 'The requested person: ',
             'person' => $jsonContent,
         ]);
    }


    #[Route('/update', name: 'update_person', methods: ['PUT'])]
    public function update_person(Request $request, ManagerRegistry $doctrine, PersonRepository $personRepository ): JsonResponse
    {
        //get the request
        $content = $request->getContent();
        $content = json_decode($content, true);
        //get the properties of the request
        $contentKeys =  array_keys($content);

        $id = $content["id"];
        //Get the person from the db
        $entityManager = $doctrine->getManager();
        $person= $personRepository->findOneBySomeField($id);

        $updatedProperties = array();

        foreach ( $contentKeys as $key) {
            if(property_exists($person,$key)) {
                $setterName = 'set' . ucfirst($key);
                if (method_exists($person, $setterName)) {
                    call_user_func([$person, $setterName], $content[$key]);
                    array_push($updatedProperties, $key);
                }
            }
        }

        $returnMessage = $this->createReturnString($updatedProperties);


        $entityManager->persist($person);
        $entityManager->flush();
        
        return $this->json([
            'message' => $returnMessage,
        ]);
    }

    
    private function createReturnString(array $updatedElements):string{
        if( count($updatedElements)>0) {
            $updatedFields= implode(', ', $updatedElements);
            $message = "The following values have been updated: ".$updatedFields;
            return $message;
        }
        else {
            return "No values have been updated";
        }

    }


    private function createPersonFromRequest($requestContent){
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
