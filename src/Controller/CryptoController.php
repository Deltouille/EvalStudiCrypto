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
        //Si le resultat retourné par la fonction getAPICryptoInfo dans la variable resultAPI est une chaine et qu'elle contient le mot 'error' alors on récupère le code erreur et on retourne une page d'erreur
        if(is_string($resultAPI) && strpos($resultAPI,'error ') !== false )
        { 
            //On enlève 'error ' dans la chaine pour ne récupérer que le code erreur
            $errorCode = str_replace('error ', '', $resultAPI);
            //On retourne une page d'erreur qui afficheras un message en fonction du code d'erreur rencontré et qui rechargeras la page d'accueil 1 minute après
            return $this->render('crypto/error_page.html.twig', ['message' => $this->getErrorMessageAPI($errorCode)]);
        }
        //On calcul la valorisation en passant le tableau contenant les informations des cryptomonnaie dans la fonction calculValorisation
        $valorisation = $this->calculValorisation($resultAPI);
        //On regarde si on trouve un champs qui a la même date que la data d'aujourd'hui dans la table 'SauvegardeJournalière' de la base de donnée, si non on enregistre la date du jour avec la valorisation
        if($sauvegardeJournaliere->findByDate($aujourdhui) == null){
            //On créer une nouvelle sauvegarde journalière
            $sauvegarde = new SauvegardeJournaliere();
            //On lui passe la date du jour
            $sauvegarde->setDate($aujourdhui);
            //On lui passe la valorisation arrondie du jour
            $sauvegarde->setValorisationTotale(round($valorisation));
            //On persist dans la base de donnée
            $em->persist($sauvegarde);
            //On flush
            $em->flush();
        }
        //On retourne la page d'accueil qui afficheras les cryptomonnaies sauvegardées dans la base de données, la valorisation actuelle
        return $this->render('crypto/accueil.html.twig', ['listeCrypto' => $listeCrypto, 'valorisation' => $valorisation, 'resultAPI' => $resultAPI]);
    }

    /**
     * La fonction calculValorisation vas servir a calculer la valorisation des cryptomonnaies présentes dans la base de donnée par rapport aux prix actuel récupérer par l'API en prenant en compte la quantité
     * La fonction reçoit le tableau contenant toutes les informations des cryptomonnaies
     */
    public function calculValorisation($tableauCrypto){
        //On créer un tableau "listeAPIPrice" qui vas servir a récupérer le total des prix de chaque cryptomonnaie via l'api par rapport a la quantité dans la base de donnée
        $listeAPIPrice = array();
        //On créer un tableau "listeBDDPrice" qui vas servir a récupèrer le prix total de chaque cryptomonnaie présente dans la base de données
        $listeBDDPrice = array();
        //On récupère l'entityManager
        $em = $this->getDoctrine()->getManager();
        //On récupère le repository de la classe Cryptocurrency
        $cryptoRepository = $em->getRepository(Cryptocurrency::class);
        //On récupère toutes les cryptos présentent dans la base de données
        $listeCrypto = $cryptoRepository->findAll();
        //On parcours toutes les cryptomonnaies
        foreach($listeCrypto as $cryptoEnCours){
            //On push dans le tableau "listeBDDPrice" le prix total de la cryptomonnaie courrante
            array_push($listeBDDPrice, $cryptoEnCours->getTotalPrice());
            //On push dans le tableau "listeAPIPrice" le prix actuel de la cryptomonnaie récupérer via l'API cumulé a la quantité présente dans la base de donnée pour la cryptomonnaie courrante
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
     * 
     * La fonction graph vas servir a afficher un graphique de toutes les valorisations chaque jours, présentes dans la base de donnée
     * Elle fonctionne de cette façon : 
     * - Elle récupère tout les champs de la table "SauvegardeJournalière" dans la base de donnée
     * - Pour chaque sauvegarde elle enregistre la date et la valorisation dans 2 tableau
     * - Elle met les dates comme labels pour l'axe des abscisses
     * - Elle met la valeur minimale et maximale présente dans le tableau des valorisations pour l'axe des ordonnées   
     */
    public function graph(ChartBuilderInterface $chartBuilder): Response
    {
        //On récupère l'entityManager
        $em = $this->getDoctrine()->getManager();
        //On récupère le repository des sauvegarde journalières
        $sauvegardeJournaliere = $em->getRepository(SauvegardeJournaliere::class);
        //On récupère tout les champs dans la table
        $listeSauvegarde = $sauvegardeJournaliere->findAll();
        //On créer un tableau qui vas servir a stocker les dates
        $listeSauvegardeDates = array();
        //On créer un tableau qui vas servir a stocker les valorisations
        $listeSauvegardeValorisation = array(); 
        //On parcours chaque sauvegarde
        foreach($listeSauvegarde as $laSauvegarde){
            //On enregistre la date de la sauvegarde courante dans le tableau listeSauvegardeDate
            array_push($listeSauvegardeDates, $laSauvegarde->getDate());
            //On enregistre la valorisation de la sauvegarde courante dans le tableau listeSauvegardeDate
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
                    ['ticks' => ['min' => min($listeSauvegardeValorisation), 'max' => max($listeSauvegardeValorisation)]],
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
     * La fonction suppressionMontant vas servir a enlever une certaine quantité d'une cryptomonnaie choisie.
     * Elle fonctionne de cette façon :
     * - Après avoir choisis la cryptomonnaie a modifier depuis la page d'accueil, on choisis la quantité a retirer
     * - Au moment d'appuyer sur le boutton "Submit", la fonction vas récuperer la quantité a enlever dans le formulaire et la soustraire a la quantité existante en base de donnée,
     *   pour avoir la quantité finale. Une fois cela fait, la fonction vas récupérer la valeur actuelle de la cryptomonnaie grâce a l'API de CoinMarketBase et vas ensuite calculer le nouveau total
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
        //On vérifie si la cryptomonnaie choisis existe dans la base de données
        if($suppressionMontant === null){
            $message = 'La crypto monnaie choisis n\'existe pas dans la base de données';
            return $this->render('crypto/error_page.html.twig', ['message' => $message]);
        }
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
                //Si la nouvelle quantité est inférieur ou égale a 0
                if($newQuantity <= 0){
                    //On supprime la cryptomonnaie de la base de données
                    $em->remove($suppressionMontant);
                    $em->flush();
                    //On redirige vers l'accueil
                    $this->redirectToRoute('accueil');
                }
                //On récupère le prix courrant de la cryptomonnaie
                $infoCrypto = $this->getAPICryptoInfo($cryptoSymbol);
                //Si le resultat retourné par la fonction getAPICryptoInfo dans la variable resultAPI est une chaine et qu'elle contient le mot 'error' alors on récupère le code erreur et on retourne une page d'erreur
                if(is_string($infoCrypto) && strpos($infoCrypto,'error ') !== false )
                { 
                    //On enlève 'error ' dans la chaine pour ne récupérer que le code erreur
                    $errorCode = str_replace('error ', '', $infoCrypto);
                    //On retourne une page d'erreur qui afficheras un message en fonction du code d'erreur rencontré et qui rechargeras la page d'accueil 1 minute après
                    return $this->render('crypto/error_page.html.twig', ['message' => $this->getErrorMessageAPI($errorCode)]);
                }
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

    /**
     * La fonction getAPICryptoInfo vas servir a récupérer les informations des cryptomonnaies voulus grâce a l'API de COINMARKETCAP
     * 
     * Elle fonctionne de cette façon : 
     * - Elle récupère un ou plusieurs "symbols" de cryptomonnaie en paramètre (par exemple : BTC, ETH, ADA,...) sous forme d'une chaine de charactère qui ressemble a : 'BTC' si on souhaite ne récupérer
     *   que les infos d'une seule crypto et qui ressemble a 'BTC,ETH,ADA,DOGE' si on souhaite récupérer les informations de plusieurs cryptomonnaie en une seule fois
     * - Elle récupère la clé d'API stockée en base de données et place la clé dans le tableau $headers.
     * - Elle met dans la chaine de charactère des "Symbols" dans le tableau $parameters, associé a la clé "symbol" qui vas nous permettre de demandé a l'API de récupérer les informations des cryptomonnaies demandées.
     * - L'API nous retourne un JSON qui sera convertit en tableau que l'on vas récupérer.
     * - La fonction contient aussi une gestion d'erreur qui vérifie le code erreur retourné par l'API (par exemple en cas de mauvaise clé api)
     */
    public function getAPICryptoInfo($listeCrypto){
        //On récupère l'entityManager
        $em = $this->getDoctrine()->getManager();
        //On récupère le repository de la classe API
        $apiRepository = $em->getRepository(API::class);
        //On récupère toutes les clé API présente en base de donnée (Il n'y en as que une)
        $getAPI = $apiRepository->findAll();
        setlocale(LC_MONETARY, 'fr_FR.UTF-8');
        //L'url de l'api servant a récupérer les informations des cryptomonnaies
        $url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest';
        //Les parametres qu'on envoie a l'API
        $parameters = [
                //Le ou les "Symbols" passés en paramètres de la fonction ('BTC'/'BTC,ETH,ADA')
                'symbol' => $listeCrypto,
                //Comment le prix doit être retourné par l'API (EUR = EURO, USD = US Dollars, etc... )
                'convert' => 'EUR'
            ];
        //Les Headers
        $headers = [
                //On demande a l'API de retourner la réponse en JSON
                'Accepts: application/json',
                //On passe notre clé API
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
        //On transforme la réponse JSON de l'api en tableau
        $var = json_decode($response, true);
        //On regarde si le code erreur retrouné par l'API est différent de 0
        if($var['status']['error_code'] !== 0){
            //Si il l'est alors on retourne une chaine comportant 'error ' + le code erreur retourné par l'API
            return 'error '.$var['status']['error_code'];
        }
        //On retourne les informations des cryptomonnaies
        return $var['data'];
    }

    /**
     * La fonction getErrorMessageAPI vas servir a récupérer le message correspondant en cas d'erreur avec l'API
     */
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
