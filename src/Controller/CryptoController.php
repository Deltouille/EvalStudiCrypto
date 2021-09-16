<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Cryptocurrency;
use App\Form\CryptoType;
use App\Form\CryptoModificationType;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
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
        $listeCurrentTotalAPIPrice = array();

        foreach($listeCrypto as $crypto)
        {
            //On récupère le prix actuel de la crypto en cours grâce a l'API
            $currentAPIPrice = $this->getCryptoPrice($crypto->getName());
            //On enregistre les valeurs dans un tableau
            $listeCurrentAPIPrice[$crypto->getName()] = $currentAPIPrice;
            //On calcule le total du prix actuel de la crypto avec la quantité que l'on as dans la base de donnée
            $currentTotalAPIPrice = $currentAPIPrice * $crypto->getQuantity();
            array_push($listePriceAchat, $crypto->getTotalPrice());
            array_push($listeCurrentTotalAPIPrice, $currentTotalAPIPrice);
        }
        //On calcul le total des prix totaux de chaque crypto présent dans la base de données
        $totalPriceAchat = array_sum($listePriceAchat);
        //On calcul le prix total des crypto a ce moment avec la même quantité de crypto que celles présentes dans la base de données
        $totalAPIPrice = array_sum($listeCurrentTotalAPIPrice);
        //On calcule la valorisation
        $valorisation = $totalAPIPrice - $totalPriceAchat;
        return $this->render('crypto/accueil.html.twig', ['listeCrypto' => $listeCrypto, 'valorisation' => $valorisation, 'listeCurrentAPIPrice' => $listeCurrentAPIPrice]);
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
    public function graph(ChartBuilderInterface $chartBuilder): Response
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            'datasets' => [
                [
                    'label' => 'My First dataset',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [0, 10, 5, 2, 20, 30, 45],
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'yAxes' => [
                    ['ticks' => ['min' => 0, 'max' => 100]],
                ],
            ],
        ]);

        return $this->render('crypto/chart.html.twig', [
            'chart' => $chart,
        ]);
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
                'X-CMC_PRO_API_KEY: no lol',
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
