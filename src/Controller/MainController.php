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
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\NoResultException;



class MainController extends AbstractController
{

    public function __construct(PersonRepository $personRepository, ManagerRegistry $doctrine)
    {
        $this->entityManager = $doctrine->getManager();
        $this->personRepository =  $personRepository;
    }
    
  
    #[Route('/add', name: 'add_person', methods: ['POST'])]
    public function add_person(Request $request): JsonResponse
    {
      try {
        $content = $this->getRequestContent(true,$request);
        if(empty($content)) {
            throw new InvalidArgumentException("Json request body is empty");
        }
        $person=$this->createPersonFromRequest($content);
        $this->entityManager->persist($person);
        $this->entityManager->flush();
      } catch (InvalidArgumentException $e) {
        return $this->json([
             'error' => 'An argument error happened in the request ',
             'message' => $e->getMessage(),
        ],400);
      }
      catch (\Doctrine\ORM\ORMException $e){
        return $this->json([
            'error' => 'A database error occurred',
            'message' => $e->getMessage(),
        ], 500);
    }

      catch(\Exception $e) {
        return $this->json([
            'error' => 'An error occurred',
            'message' => $e->getMessage(),
        ], 500);
      }


        return $this->json([
            'message' => 'Saved person in database: ',
             'firstname' => $person->getFirstname(),
             'lastname' => $person->getLastname(),
        ]);
    }


    #[Route('/get', name: 'get_person', methods: ['GET'])]
    public function get_person(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try{
        $encoder = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoder);
        $content = $this->getRequestContent(true,$request);
        if(empty($content)) {
            throw new \InvalidArgumentException("Id is missing in the request or request is empty");
        }
        $id = $content["id"];
        $person= $this->personRepository->findOneBySomeField($id);
        if(!$person ) {
            throw new NoResultException();
        }
        $jsonContent = $serializer->serialize($person, 'json');
        }

        catch (\InvalidArgumentException $e) {
            return $this->json([
                 'error' => 'An argument error happened in the request ',
                 'message' => $e->getMessage(),
            ],400);
          }

          catch (NoResultException $e) {
            return $this->json([
                 'error' => 'No result found',
                 'message' => 'No person with the given id was found',
            ],400);
          }

        catch(\Exception $e) {
            return $this->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage(),
            ], 500);
          }

         return $this->json([
             'message' => 'The requested person: ',
             'person' => $jsonContent,
         ]);
    }


    #[Route('/del', name: 'del_person', methods: ['DELETE'])]
    public function del_person(Request $request): JsonResponse
    {
        try {
            $content = $this->getRequestContent(true,$request);
            if(empty($content)) {
                throw new \InvalidArgumentException("Id is missing in the request or request is empty");
            }
            $id = $content["id"];
            $person = $this->personRepository->findOneBySomeField($id);
            if(!$person ) {
                throw new NoResultException();
            }
            $this->entityManager->remove($person);
            $this->entityManager->flush();
        }

        catch (NoResultException $e) {
            return $this->json([
                 'error' => 'No result found',
                 'message' => 'No person with the given id was found',
            ], 400);
          }

        catch (\InvalidArgumentException $e) {
            return $this->json([
                 'error' => 'An argument error happened in the request ',
                 'message' => $e->getMessage(),
            ], 400);
          }

        catch(\Exception $e) {
            return $this->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage(),
            ], 500);
          }

         return $this->json([
             'message' => 'Deleted the Person: ',
             'person' => $person->getFirstname()." ".$person->getLastname(),
         ]);
    }


    #[Route('/update', name: 'update_person', methods: ['PUT'])]
    public function update_person(Request $request): JsonResponse
    {

       
        $content = $this->getRequestContent(true, $request);
        $contentKeys =  array_keys($content);

        $id = $content["id"];
        $person= $this->personRepository->findOneBySomeField($id);

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

        $this->entityManager->persist($person);
        $this->entityManager->flush();
        
        return $this->json([
            'message' => $returnMessage,
        ]);
    }

    private function getRequestContent(bool $returnAsAssocArray, Request $request):array{
        $content = $request->getContent();
        return json_decode($content, $returnAsAssocArray);
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
