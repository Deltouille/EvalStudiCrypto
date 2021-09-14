<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Cryptocurrency;
use App\Form\CryptoType;
use App\Form\CryptoModificationType;
class CryptoController extends AbstractController
{
    /**
     * @Route("/accueil", name="accueil")
     */
    public function index(): Response
    {
        $em = $this->getDoctrine()->getManager();
        $cryptoRepository = $em->getRepository(Cryptocurrency::class);
        $listeCrypto = $cryptoRepository->findAll();
        $listePriceAchat = array();
        $listeCurrentAPIPrice = array();
        foreach($listeCrypto as $crypto)
        {
            $currentPrice = $this->getCryptoPrice($crypto->getName());
            $currentAPIPrice = $currentPrice * $crypto->getQuantity();
            array_push($listePriceAchat, $crypto->getTotalPrice());
            array_push($listeCurrentAPIPrice, $currentAPIPrice);
        }
        $totalPriceAchat = array_sum($listePriceAchat);
        $totalAPIPrice = array_sum($listeCurrentAPIPrice);
        dd($totalAPIPrice - $totalPriceAchat);


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
                $cryptoSymbol = $crypto->getName();
                $quantity = $crypto->getQuantity();
                $price = $this->getCryptoPrice($cryptoSymbol);
                $totalPrice = $quantity * $price;
                $crypto->setTotalPrice($totalPrice);
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
     * @Route("/suppression-montant/{id}", name="suppression-montant")
     */
    public function suppressionMontant(Request $request, int $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $cryptoRepository = $em->getRepository(Cryptocurrency::class);
        $suppressionMontant = $cryptoRepository->find($id);
        $currentQuantity = $suppressionMontant->getQuantity();
        $form = $this->createForm(CryptoModificationType::class, $suppressionMontant);
        if($request->isMethod('POST')){
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                //On récupère le nom de la crypto qu'on modifie
                $cryptoSymbol = $suppressionMontant->getName();
                //On récupère la quantité qu'on souhaite lui enlever
                $quantity = $suppressionMontant->getQuantity();
                //On ajuste la quantité totale
                $newQuantity = $currentQuantity - $quantity;
                //On récupère le prix courrant de la cryptomonnaie
                $price = $this->getCryptoPrice($cryptoSymbol);
                //On ajuste le prix 
                $totalPrice = $newQuantity * $price;
                //On met a jour le prix total
                $suppressionMontant->setTotalPrice($totalPrice);
                //On met a jour la quantité
                $suppressionMontant->setQuantity($newQuantity);
                //On persist
                $em->persist($suppressionMontant);
                //On flush
                $em->flush();
                //On retourne a la page d'accueil
                return $this->redirectToRoute('accueil');
            }
        }
        return $this->render('crypto/suppression.html.twig', ['form' => $form->createView()]);
    }

    public function getCryptoPrice($cryptoSigne){
        setlocale(LC_MONETARY, 'fr_FR.UTF-8');
        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest';
        $parameters = [
                'symbol' => $cryptoSigne,
                'convert' => 'EUR'
            ];
        $headers = [
                'Accepts: application/json',
                'X-CMC_PRO_API_KEY: ',
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
        $price = $var['data'][$cryptoSigne]['quote']['EUR']['price'];
        return $price;
    }
}
