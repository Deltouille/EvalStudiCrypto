# EvalStudiCrypto - Alexandre P

http://evaluation-studi.herokuapp.com/accueil

Evaluation certifiante Studi - Crypto monnaies

Le site dispose de 5 pages. Une page “Accueil”, une page “Ajout d’une crypto monnaie", une page “Suppression d’un montant”, une page “Erreur” et une page “Graphique des valorisations”.

La page “Accueil” :
Elle permet d'accéder à la page  d’ajout d’une crypto monnaie, depuis une icône “+” présent en haut à droite de la page
Elle permet d’accéder à la page de modification d’un montant, l’icône “crayon” en haut à droite, à côté de l'icône “+”, lorsqu’on appuis dessus vas faire apparaître une petite icône de modification () en face de chaque crypto monnaie. Cliquer sur cette icône va amener à la page de suppression d’un montant pour la crypto monnaie sélectionnée.
Elle permet d’afficher la valorisation actuelle.
Elle enregistre la valorisation 1x par jour en base de données.
Elle dispose d’un tableau regroupant toutes les crypto monnaies que l’utilisateur a en base de données, en affichant à l'aide d’une flèche, si le prix actuelle de chaque crypto monnaie est en hausse ou en baisse par rapport aux prix d’achat de l’utilisateur, par exemple, imaginons que l’utilisateur ait acheté 5 bitcoin au prix unitaire (1 bitcoin) de 40000€, si le prix actuel du bitcoin est inférieur à 40000€, une flèche descendante seras afficher en face du bitcoin en question, montrant à l'utilisateur qu’il perd de l’argent, inversement si le prix actuel du bitcoin supérieur à 40000€.
Elle permet d'accéder à la page du graphique des valeurs en appuyant directement sur la valorisation du jour.

La page “Ajout d’une crypto monnaie” :
Elle affiche un formulaire permettant à l'utilisateur de choisir une cryptomonnaie parmi une liste, de rentrer la quantité achetée et le prix unitaire de la cryptomonnaie (pas le prix total). A l'envoi du formulaire, l’application calcule elle-même le prix total pour la quantité de crypto-monnaies et enregistre tout en base de données. Une fois le formulaire envoyé, l’utilisateur est redirigé sur la page d’accueil.
Elle affiche une erreur dans le cas où l'utilisateur rentre un prix ou une quantité négative.

La page “Suppression d’un montant” : 
La page est un formulaire avec un champ non modifiable affichant le nom de la crypto monnaie qui serait modifié et un champ permettant à l'utilisateur de rentrer un montant à enlever de la quantité de la crypto monnaie.
A l’envoie du formulaire, l’application va calculer la nouvelle quantité et le nouveau prix total grâce au prix actuel de la crypto monnaie retourné par l’api, et va enregistrer le tout en base de données.
L’utilisateur est ensuite redirigé sur la page d’accueil.

La page “Graphique” :
La page affiche un graphique, avec chaque valorisation de chaque jour présent en base de données. L’utilisateur peut voir la valorisation d’un jour en passant son curseur ou en appuyant sur chaque point du graphique.
La valeur minimale et maximale du graphique est en fonction de la plus petite et plus grosse valorisation présente en base de données.

La page “Erreur” : 
Lorsque l’utilisateur fait une requête à l'API de coinmarketcap depuis la page d’accueil, si l’API retourne une erreur, l’utilisateur sera redirigé sur une page d’erreur affichant le code d’erreur et le message correspondant. L’application redirige automatiquement l’utilisateur sur la page d’accueil au bout d’une minute.

Fonctionnalités communes à toutes les pages : 
Chaque page (sauf la page d’accueil) dispose d’une icône en forme de croix en haut à gauche de la page, permettant à l'utilisateur de revenir à la page d’accueil.
