<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Cryptocurrency;
use App\Entity\SauvegardeJournaliere;
use App\Entity\API;
use App\Form\CryptoType;
use App\Form\CryptoModificationType;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
class CryptoController extends AbstractController
{


    /**
     * @Route("/accueil", name="accueil")
     */
    public function accueil(): Response
    {
        //On récupère l'entityManager
        $em = $this->getDoctrine()->getManager();
        //On récupère le repository de la classe Cryptocurrency
        $cryptoRepository = $em->getRepository(Cryptocurrency::class);
        //On récupère le repository de la sauvegarde journalière
        $sauvegardeJournaliere = $em->getRepository(SauvegardeJournaliere::class);
        //On récupère TOUT les noms des cryptomonnaie présent en base de donnée, récupéré de cette façon : array(0 => ["name" => "BTC"], 1 => ["name" => "ETH"], Etc...)
        $listeCrypto = $cryptoRepository->findAll();
        //Ce tableau vas servir a garder tout les noms 
        $tableauNomCrypto = array();
        //On récupère la date d'ajourd'hui
        $aujourdhui = date('Y-m-d');
        foreach($listeCrypto as $nomCrypto){
            array_push($tableauNomCrypto, $nomCrypto->getName());
        }
        //On récupère le contenus du tableau sous forme de string, séparé par une virgule
        $stringCrypto = implode(",",$tableauNomCrypto);
        //On récupère toutes les infos grâce a l'API
        $resultAPI = $this->getAPICryptoInfo($stringCrypto);
        if(is_string($resultAPI) && strpos($resultAPI,'error ') !== false )
        { 
            $errorCode = str_replace('error ', '', $resultAPI);
            return $this->render('crypto/error_page.html.twig', ['message' => $this->getErrorMessageAPI($errorCode)]);
        }
        $valorisation = $this->calculValorisation($resultAPI);
        if($sauvegardeJournaliere->findByDate($aujourdhui) == null){
            //On créer une nouvelle sauvegarde journalière
            $sauvegarde = new SauvegardeJournaliere();
            //On lui passe la date du jour
            $sauvegarde->setDate($aujourdhui);
            //On lui passe la valorisation du jour
            $sauvegarde->setValorisationTotale(round($valorisation));
            //On persist dans la base de donnée
            $em->persist($sauvegarde);
            //On flush
            $em->flush();
        }
        return $this->render('crypto/accueil.html.twig', ['listeCrypto' => $listeCrypto, 'valorisation' => $valorisation, 'resultAPI' => $resultAPI]);
    }

    public function calculValorisation($tableauCrypto){
        $listeAPIPrice = array();
        $listeBDDPrice = array();
        //On récupère l'entityManager
        $em = $this->getDoctrine()->getManager();
        //On récupère le repository de la classe Cryptocurrency
        $cryptoRepository = $em->getRepository(Cryptocurrency::class);
        $listeCrypto = $cryptoRepository->findAll();
        foreach($listeCrypto as $cryptoEnCours){
            //var_dump('Total Price BDD : '. $cryptoEnCours->getTotalPrice());
            array_push($listeBDDPrice, $cryptoEnCours->getTotalPrice());
            //var_dump('Total Price API : ' . $cryptoEnCours->getQuantity()*$tableauCrypto[$cryptoEnCours->getName()]['quote']['EUR']['price']);
            array_push($listeAPIPrice, $cryptoEnCours->getQuantity()*$tableauCrypto[$cryptoEnCours->getName()]['quote']['EUR']['price']);
        }
        
        $valorisation = array_sum($listeAPIPrice) - array_sum($listeBDDPrice);
        return round($valorisation);
    }

    /**
     * @Route("/ajout", name="ajout")
     * 
     * La fonction Ajout vas servir à ajouter une nouvelle cryptomonnaie.
     * Elle fonctionne de cette façon :
     * - On selectionne une cryptommonaie
     * - On rentre la quantité que l'on à acheter
     * - On rentre le prix unitaire auquel on as acheter la cryptomonnaie (exemple : On achete 50 Ethereum au prix unitaire de 5€)
     * - Au moment d'appuyer sur le boutton Submit la fonction calculeras le prix total (50 ETH pour 5€ -> 250€) et enregisteras tout en base de donnée.
     */
    public function ajout(Request $request): Response
    {
        //On créer une nouvelle crypto
        $crypto = new Cryptocurrency();
        //On récupère l'entity manager
        $em = $this->getDoctrine()->getManager();
        //On créer un formulaire
        $form = $this->createForm(CryptoType::class, $crypto);
        //On regarde si le formulaire a été posté avec la méthode POST
        if($request->isMethod('POST')){
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                //On récupère la quantité rentré dans le formulaire
                $quantity = $crypto->getQuantity();
                //On récupère le prix rentré dans le formulaire
                $price = $crypto->getPrice();
                //On calcul le total
                $totalPrice = $quantity * $price;
                //On enregistre le total en base de donnée
                $crypto->setTotalPrice($totalPrice);
                //On persist en base de donnée
                $em->persist($crypto);
                //On flush
                $em->flush();
                //On retourne sur la page d'accueil
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
        $em = $this->getDoctrine()->getManager();
        $sauvegardeJournaliere = $em->getRepository(SauvegardeJournaliere::class);
        $listeSauvegarde = $sauvegardeJournaliere->findAll();
        $listeSauvegardeDates = array();
        $listeSauvegardeValorisation = array(); 
        foreach($listeSauvegarde as $laSauvegarde){
            array_push($listeSauvegardeDates, $laSauvegarde->getDate());
            array_push($listeSauvegardeValorisation, $laSauvegarde->getValorisationTotale());
        }
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $listeSauvegardeDates,
            'datasets' => [
                [
                    'label' => 'Valorisation',
                    'borderColor' => 'rgb(31,195,108)',
                    'data' => $listeSauvegardeValorisation,
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'yAxes' => [
                    ['ticks' => ['min' => 0, 'max' => 50000]],
                ],
            ],
        ]);

        return $this->render('crypto/chart.html.twig', [
            'chart' => $chart,
        ]);
    }
   
    /**
     * @Route("/suppression-montant/{id}", name="suppression-montant")
     * 
     * La fonctionne suppressionMontant vas servir a enlever une certaine quantité d'une cryptomonnaie choisie.
     * Elle fonctionne de cette façon :
     * - Après avoir choisis la cryptomonnaie a modifier depuis la page d'accueil, on choisis la quantité a retirer
     * - Au moment d'appuyer sur le boutton "Submit", la fonction vas récuperer la quantité a enlever dans le formulaire et la soustraire a la quantité existante en base de donnée,
     *   pour avoir la quantité finale. Une fois cela fait, la fonction vas récupèrer la valeur actuelle de la cryptomonnaie grâce a l'API de CoinMarketBase et vas ensuite calculer le nouveau total
     *   pour l'enregistrer dans la base de donnée
     * 
     * /!\ La valeur par défaut dans le formulaire du montant est celui enregistré dans la base de donnée /!\
     */
    public function suppressionMontant(Request $request, int $id): Response
    {
        //On récupère l'entity manager
        $em = $this->getDoctrine()->getManager();
        //On récupère le repository de la table "Cryptocurrency"
        $cryptoRepository = $em->getRepository(Cryptocurrency::class);
        //On récupère la cryptomonnaie a modifier
        $suppressionMontant = $cryptoRepository->find($id);
        //On récupère la quantité présente en base de donnée
        $currentQuantity = $suppressionMontant->getQuantity();
        //On créer un formulaire
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
                $infoCrypto = $this->getAPICryptoInfo($cryptoSymbol);
                //On ajuste le prix 
                $totalPrice = $newQuantity * $infoCrypto[$cryptoSymbol]['quote']['EUR']['price'];
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


    public function getAPICryptoInfo($listeCrypto){
        $em = $this->getDoctrine()->getManager();
        $apiRepository = $em->getRepository(API::class);
        $getAPI = $apiRepository->findAll();
        setlocale(LC_MONETARY, 'fr_FR.UTF-8');
        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest';
        $parameters = [
                'symbol' => $listeCrypto,
                'convert' => 'EUR'
            ];
        $headers = [
                'Accepts: application/json',
                'X-CMC_PRO_API_KEY: '.$getAPI[0]->getAPI(),
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
        
        if($var['status']['error_code'] !== 0){
            dd($var);
            return 'error '.$var['status']['error_code'];
        }
        return $var['data'];
    }

    

    public function getErrorMessageAPI($code){
        switch($code){
            case '1001':
                return  "This API Key is invalid.";
                break;
            case '1002':
                return "API key missing.";
                break;
            case '1003':
                return "Your API Key must be activated. Please go to pro.coinmarketcap.com/account/plan.";
                break;
            case '1004':
                return "Your API Key's subscription plan has expired.";
                break;
            case '1005':
                return "An API Key is required for this call.";
                break;
            case '1006':
                return "Your API Key subscription plan doesn't support this endpoint.";
                break;
            case '1007':
                return "This API Key has been disabled. Please contact support.";
                break;
            case '1008':
                return "You've exceeded your API Key's HTTP request rate limit. Rate limits reset every minute.";
                break;
            case '1009':
                return "You've exceeded your API Key's daily rate limit.";
                break;
            case '1010':
                return "You've exceeded your API Key's monthly rate limit.";
                break;
            case '1011':
                return "You've hit an IP rate limit.";
                break;
        }
    }

    /**
     * @Route("/", name="home")
     */
    public function home(){
        return $this->redirectToRoute('accueil');
    }
}
