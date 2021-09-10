<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Cryptocurrency;
use App\Form\CryptoType;

class CryptoController extends AbstractController
{
    /**
     * @Route("/accueil", name="acueil")
     */
    public function index(): Response
    {
        $nomCrypto = array();
        $em = $this->getDoctrine()->getManager();
        $cryptoRepository = $em->getRepository(Cryptocurrency::class);
        $listeCrypto = $cryptoRepository->findAll();

        foreach($listeCrypto as $crypto){
            array_push($nomCrypto, $crypto->getName());
        }
    
        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest';
        $parameters = [
                'symbol' => implode(",",$nomCrypto),
                'convert' => 'EUR'
            ];
        $headers = [
                'Accepts: application/json',
                'X-CMC_PRO_API_KEY: not for you',
            ];
        $qs = http_build_query($parameters); // query string encode the parameters
        $request = "{$url}?{$qs}"; // create the request URL
        $curl = curl_init(); // Get cURL resource
        // Set cURL options
        curl_setopt_array($curl, array(
          CURLOPT_URL => $request,            // set the request URL
          CURLOPT_HTTPHEADER => $headers,     // set the headers 
          CURLOPT_RETURNTRANSFER => 1         // ask for raw response instead of bool
       ));
            
        $response = curl_exec($curl); // Send the request, save the response
        curl_close($curl); // Close request
        $var = json_decode($response, true);
        dd($var['data']);

        return $this->render('crypto/accueil.html.twig', ['listeCrypto' => $listeCrypto]);
    }

    /**
     * @Route("/ajout", name="ajout")
     */
    public function ajout(Request $request): Response
    {
        $crypto = new Cryptocurrency();
        $form = $this->createForm(CryptoType::class, $crypto);
        if($request->isMethod('POST')){
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $em = $this->getDoctrine()->getManager();
                $em->persist($crypto);
                $em->flush();
                return $this->redirectToRoute('accueil');
            }
        }
        return $this->render('crypto/ajout.html.twig', ['crypto' => $crypto, 'form' => $form->createView()]);
    }

    /**
     * @Route("/graph", name="graph")
     */
    public function graph(): Response
    {

    }

    /**
     * @Route("/suppression/{id}", name="suppression")
     */
    public function suppression(): Response
    {
    
    }
}
