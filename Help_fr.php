<?php
/**
 * French Help texts
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * @author François Jacquet
 *
 * @uses Heredoc syntax
 * @see  http://php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
 *
 * @package RosarioSIS
 * @subpackage Help
 */

// DEFAULT.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['default'] = <<<HTML
<p>
	Comme administrateur, vous pouvez configurer les écoles du système, modifier les élèves et les utilisateurs, et accéder à des rapports essentiels sur les élèves.
</p>
<p>
	Vous avez accès à toutes les écoles du système. Pour choisir l'école courante, utilisez le menu déroulant du menu latéral et sélectionnez une école. Le programme rafraichira automatiquement la page avec la nouvelle école dans l'espace de travail. Vous pouvez, de manière similaire, changer l'année scolaire et la période scolaire courante.
</p>
<p>
	En utilisant RosarioSIS, vous noterez d'autres éléments pouvant apparaître dans votre menu latéral. Quand vous sélectionnez un élève, le nom de l'élève précédé d'une croix apparaît en dessous du menu déroulant des périodes scolaires. Lorsque vous changez de programme, vous continuerez de travailler avec cet élève. Si vous souhaitez changer d'élève courant, cliquez sur la croix devant le nom de l'élève. Vous pouvez aussi accéder rapidement aux Informations Générales de l'élève en cliquant sur le nom de l'élève.
</p>
<p>
	Si vous sélectionnez un utilisateur, son nom apparaîtra aussi dans le menu latéral. Le comportement sera identique à celui du nom de l'élève.
</p>
<p>
	Aussi, quand vous cliquez sur une icône du menu latéral, vous verrez la liste des programmes disponibles dans ce module. Le fait de cliquer sur un titre de programme lancera celui-ci dans l'espace de travail, et actualisera le texte d'aide en ligne.
</p>
<p>
	À différents endroits dans RosarioSIS, vous verrez des listes de données qui peuvent être modifiées. Souvent, vous devrez d'abord cliquer sur la valeur que vous souhaitez modifier afin d'avoir accès au champ de saisie. Ensuite, une fois modifié et enregistré, le champ de valeur retournera à sa forme originale.
</p>
<p>
	Vous pouvez vous déconnecter de RosarioSIS à n'importe quel moment en cliquant sur le bouton "Déconnexion" du menu inférieur.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'teacher' ) :

	$help['default'] = <<<HTML
<p>
	Comme enseignant, vous avez accès aux informations et emplois du temps des élèves qui sont dans vos classes et pour lesquels vous prenez les absences, saisissez les notes, et l'éligibilité. Vous avez aussi à votre disposition le programme de carnet de notes afin de garder trace des notes des élèves. Le Carnet de Notes est intégré au programme de Saisie des Notes ainsi qu'au programme d'Éligibilité. Grâce au Carnet de Notes, vous pourrez non seulement garder trace des notes, mais aussi imprimer des bulletins intermédiaires pour chacun de vos élèves.
</p>
<p>
	Afin de définir la classe courante, vous devrez la sélectionner depuis le menu déroulant du menu latéral. Le programme de l'espace de travail sera alors automatiquement rafraichit avec la nouvelle classe. Vous pouvez, de manière similaire, changer l'année scolaire et la période scolaire courante.
</p>
<p>
	En utilisant RosarioSIS, vous noterez d'autre éléments apparaître dans votre menu latéral. Quand vous sélectionnez un élève, le nom de l'élève précédé d'une croix apparaît en dessous du menu déroulant des périodes scolaires. Lorsque vous changez de programme, vous continuerez de travailler avec cet élève. Si vous souhaitez changer d'élève courant, cliquez sur la croix devant le nom de l'élève.
</p>
<p>
	Aussi, quand vous cliquez sur une icône du menu latéral, vous verrez la liste des programmes disponibles dans ce module. Le fait de cliquer sur un titre de programme lancera celui-ci dans l'espace de travail, et actualisera le texte d'aide en ligne.
</p>
<p>
	Dans le carnet de notes, vous verrez des listes de données qui peuvent être modifiées. Souvent, vous devrez d'abord cliquer sur la valeur que vous souhaitez modifier afin d'avoir accès au champ de saisie. Ensuite, une fois modifié et enregistré, le champ de valeur retournera à sa forme originale.
</p>
<p>
	Vous pouvez vous déconnecter de RosarioSIS à n'importe quel moment en cliquant sur le bouton "Déconnexion" du menu inférieur.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'parent' ) :

	$help['default'] = <<<HTML
<p>
	Comme parent, vous pouvez consulter les informations de l'élève, leurs emploi du temps, devoirs, notes, éligibilité et leurs absences.
</p>
<p>
	Afin de choisir l'élève courant, vous devrez le sélectionner depuis le menu déroulant du menu latéral. Le programme de l'espace de travail sera alors automatiquement rafraichit avec le nouvel élève. Vous pouvez, de manière similaire, changer l'année scolaire et la période scolaire courante.
</p>
<p>
	En utilisant RosarioSIS, vous noterez d'autre éléments apparaître dans votre menu latéral. Quand vous sélectionnez un élève, le nom de l'élève précédé d'une croix apparaît en dessous du menu déroulant des périodes scolaires. Lorsque vous changez de programme, vous continuerez de travailler avec cet élève. Si vous souhaitez changer d'élève courant, cliquez sur la croix devant le nom de l'élève.
</p>
<p>
	Vous pouvez vous déconnecter de RosarioSIS à n'importe quel moment en cliquant sur le bouton "Déconnexion" du menu inférieur.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'student' ) :

	$help['default'] = <<<HTML
<p>
	Comme élève, vous avez accès à vos informations, votre emploi du temps, vos devoirs, notes, éligibilité et vos absences.
</p>
<p>
	Vous pouvez changer l'année scolaire et la période scolaire courante grace aux menus déroulants du menu latéral.
</p>
<p>
	En utilisant RosarioSIS, vous noterez d'autre éléments apparaître dans votre menu latéral. Quand vous sélectionnez un élève, le nom de l'élève précédé d'une croix apparaît en dessous du menu déroulant des périodes scolaires. Lorsque vous changez de programme, vous continuerez de travailler avec cet élève. Si vous souhaitez changer d'élève courant, cliquez sur la croix devant le nom de l'élève.
</p>
<p>
	Vous pouvez vous déconnecter de RosarioSIS à n'importe quel moment en cliquant sur le bouton "Déconnexion" du menu inférieur.
</p>
HTML;

endif;


// SCHOOL SETUP ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['School_Setup/Schools.php'] = <<<HTML
<p>
	<i>Informations de l'école</i> vous permet de modifier le nom, l'adresse et le principal de l'école courante. Cliquez sur n'importe quelle information de l'école pour la changer. Une fois les modifications nécessaires effectuées, cliquez sur "Enregistrer" pour sauvegarder vos modifications.
</p>
HTML;

	$help['School_Setup/Schools.php&new_school=true'] = <<<HTML
<p>
	<i>Ajouter une école</i> vous permet d'ajouter une école au système. Complétez les informations de l'école, et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour passer à la nouvelle école, sélectionnez-la dans le menu déroulant des écoles du menu latéral.
</p>
HTML;

	$help['School_Setup/CopySchool.php'] = <<<HTML
<p>
	<i>Copier l'école</i> est une méthode pratique pour ajouter une autre école à RosarioSIS, ou les Tranches Horaires, Périodes Scolaires, Niveaux Scolaires, Échelles de Notation et Codes de Présence sont similaires à l'école que vous copiez. Vous serez bien sûr en mesure de changer la configuration une fois l'école "copiée".
</p>
<p>
	Si vous ne souhaitez pas copier un ou plusieurs de ces éléments, cliquez sur la case à cocher correspondant à l'élément.
</p>
<p>
	Ensuite, entrez le nom de la nouvelle école dans le champ texte "Titre de la nouvelle École".
</p>
<p>
	Finalement, cliquez sur "OK" pour créer la nouvelle école avec les valeurs de l'école existante.
</p>
HTML;

	$help['School_Setup/MarkingPeriods.php'] = <<<HTML
<p>
	<i>Périodes Scolaires</i> vous permet de configurer les périodes scolaires de votre école. Il existe trois types de périodes scolaires: les Semestres, Trimestres et Périodes Intermédiaires. Cependant, il est tout à fait possible d'avoir plus ou moins 2 semestres, et plus ou moins 4 trimestres. De manière similaire, vous pouvez avoir autant de périodes intermédiaires que vous souhaitez dans un trimestre donné.
</p>
<p>
	Pour ajouter une période scolaire, cliquez sur l'icône Ajouter (+) dans la colonne correspondant au type de période scolaire que vous souhaitez ajouter. Ensuite, remplissez les informations de la période scolaire dans les champs au-dessus de la liste des périodes scolaires et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Les dates "Début de Saisie des Notes" et "Fin de Saisie des Notes" définissent le premier et le dernier jour de la période durant laquelle les enseignants peuvent saisir les notes définitives.
</p>
<p>
	Pour modifier une période scolaire, cliquez sur la période scolaire que vous souhaitez modifier, et cliquez sur la valeur que vous souhaitez changer dans la zone au-dessus de la liste de périodes scolaires. Ensuite, éditez la valeur et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour supprimer une période scolaire, sélectionnez-la en cliquant sur son titre dans la liste et cliquez sur le bouton "Effacer" en haut de l'écran. Vous devrez confirmer la suppression.
</p>
<p>
	Notez que deux périodes scolaires de même niveau ne peuvent se chevaucher. Aussi, deux périodes scolaires de même niveau ne devraient pas avoir le même ordre de tri.
</p>
HTML;

	$help['School_Setup/Calendar.php'] = <<<HTML
<p>
	<i>Calendriers</i> vous permet de configurer le calendrier de votre école pour l'année. La vue du calendrier montre le mois courant par défaut. Le mois et l'année peuvent être changés en utilisant les menus déroulants en haut de l'écran.
</p>
<p>
	Les jours d'école complets, la case à cocher dans le coin supérieur droit du carré du jour devrait être cochée. Pour les jours partiels, la case à cocher devrait être décochée et le nombre de minutes d'école ce jour-ci devrait être entré dans le champ texte à côté de la case à cocher. Pour les jours sans école, la case à cocher devrait être décochée et le champ texte devrait être vide. Pour décocher la case à cocher ou changer le nombre de minutes du jour d'école, vous devez d'abord cliquer sur la valeur que vous désirez changer. Après avoir fait les changements, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Pour configurer votre calendrier au début de l'année, vous devriez utiliser les liens "Créer nouveau calendrier" ou "Recréer ce calendrier". En cliquant sur ce lien dans le coin supérieur gauche de l'écran, vous pouvez configurer tous les jours d'une période de temps spécifique comme jours d'école complets. Vous pouvez aussi définir les jours de la semaine où votre école est ouverte. Après avoir sélectionné les dates de début et de fin, les jours d'ouverture, cliquez sur le bouton "OK". Vous pouvez maintenant explorer le calendrier et définir les vacances et les jours partiels.
</p>
<p>
	Le calendrier montre aussi les événements de l'école. Cela peut inclure tout évènement depuis les jours de formation continue des enseignants aux évènements sportifs. Les évènements sont visibles aussi bien par les autres administrateurs que les parents et les enseignants de votre école.
</p>
<p>
	Pour ajouter un évènement, cliquez sur l'icône ajouter (+) dans le coin inférieur de la date de l'évènement. Dans la fenêtre popup qui apparaît, saisissez les informations de l'évènement et cliquez sur le bouton "Enregistrer". La fenêtre popup se fermera, et le calendrier sera automatiquement rafraîchi pour afficher le nouvel évènement.
</p>
<p>
	Pour modifier un évènement, cliquez sur l'évènement souhaité, et changez les informations de l'évènement dans la fenêtre popup qui apparaît en cliquant sur les valeurs que vous souhaitez changer. Cliquez sur le bouton "Enregistrer". La fenêtre popup se fermera, et le calendrier sera automatiquement rafraîchi pour afficher le changement.
</p>
<p>
	Si l'école utilise la Rotation de Jours Numérotés, le numéro associé au jour est affiché dans le carré du jour.
</p>
HTML;

	$help['School_Setup/Periods.php'] = <<<HTML
<p>
	<i>Tranches Horaires</i> vous permet de configurer les tranches horaires de votre école. Les collèges et lycées auront sûrement beaucoup de tranches horaires; au contraire, les primaires auront probablement une seule tranche horaire (appelée Journée) ou peut-être 3 (Journée, Matin, et Après-midi).
</p>
<p>
	Pour ajouter une tranche horaire, remplissez les champs titre, nom abrégé, ordre de tri, et durée en minutes dans la denière ligne de la liste des tranches horaire et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Les Blocs servent à définir des Tranches horaires irrégulières valides certains jours spécifiques. Voir le programme <i>Calendriers</i> pour la configuration.
</p>
<p>
	Pour modifier une tranche horaire, cliquez sur une information de la tranche horaire, changez sa valeur, et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour supprimer une tranche horaire, cliquez sur l'icône effacer (-) à côté de la tranche horaire que vous souhaitez supprimer. Vous devrez confirmer la suppression.
</p>
HTML;

	$help['School_Setup/GradeLevels.php'] = <<<HTML
<p>
	<i>Niveaux Scolaires</i> vous permet de configurer les niveaux scolaires de votre école.
</p>
<p>
	Pour ajouter un niveau scolaire, remplissez les champs titre, nom abrégé, ordre de tri, et niveau supérieur dans la denière ligne de la liste des niveaux scolaires et cliquez sur le bouton "Enregistrer". Le champ "Niveau Supérieur" indique le niveau auquel les élèves du niveau courant seront promus l'année suivante.
</p>
<p>
	Pour modifier un niveau scolaire, cliquez sur une information du niveau scolaire, changez sa valeur, et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour supprimer un niveau scolaire, cliquez sur l'icône effacer (-) à côté du niveau scolaire que vous souhaitez supprimer. Vous devrez confirmer la suppression.
</p>
HTML;

	$help['School_Setup/Rollover.php'] = <<<HTML
<p>
	<i>Report Final</i> copie les données de l'année courante à l'année scolaire suivante. Les élèves sont promus au niveau scolaire supérieur, et les informations de l'école sont dupliquées pour l'année scolaire suivante.
</p>
<p>
	Les informations copiées incluent les tranches horaire, périodes scolaires, utilisateurs, cours, inscriptions des élèves, codes notation bulletin de notes, codes d'inscription élèves, codes de présence, et activités d'éligibilité.
</p>
HTML;

	$help['School_Setup/Configuration.php'] = <<<HTML
<p>
	<i>Configuration de l'École</i> offre divers groupes d'options de configuration pour vous aider à configurer:
</p>
<ul>
	<li>RosarioSIS lui-même:
		<ul>
			<li>
				<i>Titre du Programme</i> &amp; <i>Nom du Programme</i>: changer le nom de RosarioSIS
			</li>
			<li>Définir le <i>Thème par Défaut</i> et éventuellement le <i>Forcer</i> pour outrepasser le thème défini par les utilisateurs.
			</li>
			<li>
				<i>Créer un Compte Utilisateur</i> &amp; <i>Créer un Compte Élève</i>: activer l'inscription en ligne. Les liens "Créer un Compte Utilisateur / Élève" seront accessibles sur la page de login.
			</li>
			<li>
				<i>Champ email Élève</i>: sélectionner quel champ utiliser pour sauvegarder les emails de vos élèves. Cela peut-être le champ Nom Utilisateur ou n'importe quel autre champ texte de l'onglet Informations Générales. Définir ce champ activera de nouvelles fonctionnalités pour ou liées aux élèves au sein de RosarioSIS comme la "Réinitialisation de mot de passe".
			</li>
			<li>
				<i>Limite de Tentatives de Connexion Échouées</i>: banni l'accès durant 10 minutes lorsque la limite de tentatives de connexions échouées est atteinte. L'erreur "Trop de Tentatives de Connexion Échouées. Veuillez réessayer plus tard." sera affichée sur l'écran de login et l'entrée du Journal d'Accès correspondante aura le statut "Banni".
			</li>
		</ul>
	</li>
	<li>L'école:
		<ul>
			<li>
				<i>Année scolaire sur deux années civiles</i>: si l'année scolaire doit-être affichée au format "2014" ou "2014-2015".
			</li>
			<li>
				<i>Logo de l'École (.jpg)</i>: uploader le logo de l'école (affiché dans les Bulletins de Notes, Livrets Scolaires, Informations de l'école &amp; Imprimer Informations Élève)
			</li>
			<li>
				<i>Symbole Monétaire</i>: le symbole monétaire utilisé dans les modules Comptabilité &amp; Facturation Élèves.
			</li>
		</ul>
	</li>
	<li>Le module Élèves:
		<ul>
			<li>
				<i>Montrer l'Adresse Postale</i>: si l'adresse postale de l'élève doit être enregistrée et montrée séparément.
			</li>
			<li>
				<i>Cocher Ramassage / Dépose Car Scolaire par défaut</i>: si les cases à cocher Ramassage / Dépose Car Scolaire doivent être cochées par défaut au moment d'ajouter l'adresse d'un élève.
			</li>
			<li>
				<i>Activer les Coordonnées du Contact</i>: la possibilité d'associer des informations aux contacts de l'élève.
			</li>
			<li>
				<i>Utiliser des Commentaires Semestriels au lieu de Commentaires Trimestriels</i>: un nouveau champ de commentaires élève chaque semestre au lieu de chaque trimestre.
			</li>
			<li>
				<i>Limiter les Contacts &amp; Adresses existants à l'école courante</i>: option globale (applique à toutes les écoles) limitant les listes de Personnes &amp; Adresses à celles  associées à l'école courante de l'utilisateur au moment d'Ajouter un Contact ou une Adresse Existante.
			</li>
		</ul>
	</li>
	<li>Le module Notes:
		<ul>
			<li>
				<i>Notes</i>: si votre école utilise des pourcentages, notes (lettrées), ou les deux. Les notes (lettrées) ou les pourcentages seront alors cachés selon votre choix.
			</li>
			<li>
				<i>Cacher le commentaire de note excepté pour les cours dont les présences sont comptées</i>: si le commentaire de la note doit-être caché pour les classes sans prise de présences.
			</li>
			<li>
				<i>Permettre aux enseignants de saisir les notes après la période de saisie des notes</i>: la période de saisie des notes de chaque période scolaire est définie dans le programme Paramétrage Écoles &gt; Périodes Scolaires.
			</li>
			<li>
				<i>Activer les Statistiques Anonymes des Notes pour les Parents et Élèves</i>: les Statistiques Anonymes des Notes sont affichées dans le programme Notes des Élèves.
			</li>
		</ul>
	</li>
	<li>Le module Présence:
		<ul>
			<li>
				<i>Minutes d'une Journée Entière d'École</i>: si un élève est présent 300 minutes ou plus, RosarioSIS le marquera automatiquement Présent pour la Journée. Si un élève est présent entre 150 minutes et 299 minutes, RosarioSIS le marquera Présent pour la Demi-journée. Si un élève est présent moins de 150 minutes, RosarioSIS le marquera Absent. Si votre Journée d'École ne dure pas 300 minutes, alors ajustez le nombre de Minutes.
			</li>
			<li>
				<i>Nombre de jours avant / après la date du jour durant lesquels les professeurs peuvent éditer les absences</i>: laissez ce champ vide pour toujours autoriser l'édition.
			</li>
		</ul>
	</li>
	<li>Le module Cantine:
		<ul>
			<li>
				<i>Solde minimum de Compte Cantine pour l'avertissement</i>: définir le montant minimum en dessous duquel un avertissement sera montré à l'élève et ses parents sur le Portail et afin de générer les Rappels.
			</li>
			<li>
				<i>Solde minimum du Compte Cantine</i>: définir le montant minimum autorisé.
			</li>
			<li>
				<i>Solde cible du Compte Cantine</i>: définir le solde cible afin de pouvoir calculer le montant minimum à déposer sur le compte.
			</li>
		</ul>
	</li>
</ul>
<p>
	Onglet <b>Modules</b>: gérez les modules de RosarioSIS. Désactivez les modules que vous n'utilisez pas et installez-en de nouveaux.
</p>
<p>
	Onglet <b>Plugins</b>: gérez les plugins de RosarioSIS. Activez, désactivez et configurez les plugins. Cliquez sur le titre du plugin pour obtenir plus d'informations.
</p>
HTML;

	$help['School_Setup/SchoolFields.php'] = <<<HTML
<p>
	<i>Champs École</i> vous permet d'ajouter de nouveaux champs au programme Informations de l'école.
</p>
<p>
	Ajouter un nouveau Champ
</p>
<p>
	Cliquez sur l'icône "+" en dessous du texte "Aucun Champs École.". Renseignez le nom du champ, et choisissez quel type de champ vous souhaitez lui associer grâce au menu déroulant "Type du Champ".
</p>
<ul>
<li>
	Les champs de type "Menu Déroulant" créent des menus depuis lesquels vous pouvez sélectionner une option. Pour créer ce type de champ, cliquez sur "Menu Déroulant" et ajouter les options (une par ligne) dans le champ texte "Menu Déroulant/Menu Déroulant Automatique/Menu Déroulant Codé/Options à Choix Multiple".
</li>
<li>
	Les champs de type "Auto Menu Déroulant" créent des menus depuis lesquels vous pouvez sélectionner une option, et ajouter des options. Vous ajoutez des options en sélectionnant l'option "-Modifier-" dans les choix du menu et cliquez sur "Enregistrer". Vous pouvez dès lors modifier le champ en enlevant le texte "-Modifier-" en rouge du champ, et en entrant l'information correcte. RosarioSIS récupère toutes les options qui ont été ajoutées à ce champ au moment de créer le menu déroulant.
</li>
<li>
	Les champs de type "Menu Déroulant Modifiable" sont similaires aux champs Menu Déroulant Automatique.
</li>
<li>
	Les champs de type "Menu Déroulant Codé" sont créés en ajoutant les options au champ texte long en respectant le modèle suivant: "option affichée"|"option sauvegardée en base" (où | est le caractère "barre verticale"). Par exemple: "Deux|2", où "Deux" est affiché à l'utilisateur, ou dans une feuille de calcul téléchargée, et "2" est sauvegardé en base de données.
</li>
<li>
	Les champs de type "Menu Déroulant Exportable" sont créés en ajoutant les options au champ texte long en respectant le même modèle utilisé pour les champs "Menu Déroulant Codé" ("option affichée"|"option sauvegardée en base"). Par exemple: "Deux|2", où "Deux" est affiché à l'utilisateur, et "2" est la valeur dans une feuille de calcul téléchargée, mais "Deux" est sauvegardé en base de données.
</li>
<li>
	Les champs de type "Options à Choix Multiple" créent des cases à cocher multiples afin de pouvoir choisir une ou plusieurs options.
</li>
<li>
	Les champs de type "Texte" créent un champ texte alphanumérique ayant une capacité maximum de 255 caractères.
</li>
<li>
	Les champs de type "Texte Long" créent un grand champ de texte alphanumérique pouvant recevoir un maximum de 5000 caractères.
</li>
<li>
	Les champs de type "Case à Cocher" créent des cases à cocher. Lorsque elle est cochée sa signification est "oui", et décochée, sa signification est "non".
</li>
<li>
	Les champs de type "Nombre" créent des champs texte qui acceptent seulement des valeurs numériques.
</li>
<li>
	Les champs de type "Date" créent des menus déroulants afin de pouvoir sélectionner un date.
</li>
</ul>
<p>
	La case à cocher "Obligatoire", si cochée, rendra le champ obligatoire de façon que si le champ est laissé vide au moment de sauvegarder la page, une erreur sera affichée.
</p>
<p>
	L'"Ordre de Tri" détermine l'ordre dans lequel les champs sont affichés dans l'onglet des Informations de l'école.
</p>
<p>
	Supprimer un champ
</p>
<p>
	Vous pouvez supprimer n'importe quel champ École en cliquant sur le bouton "Effacer" dans le coin supérieur droit. Veuillez noter que vous perdrez toutes vos données liées au champ si vous supprimez un champ déjà en usage.
</p>
HTML;


	// Enseignant & Parent & Élève.
else :

	$help['School_Setup/Schools.php'] = <<<HTML
<p>
	<i>Informations de l'école</i> affiche le nom, l'adresse, et le principal de l'école courante.
</p>
HTML;

	$help['School_Setup/Calendar.php'] = <<<HTML
<p>
	<i>Calendriers</i> affiche les évènements de l'école ainsi que les devoirs des élèves. Le calendrier montre aussi si le jour est un jour d'école ou non. Par défaut, le calendrier affiche le mois courant. Le mois et l'année peuvent être changés en utilisant les menus déroulants en haut de l'écran.
</p>
<p>
	Les titres des évènements de l'école et les devoirs sont affichés dans chaque carré de jour. Le fait de cliquer sur les titres ouvrira une fenêtre popup montrant plus d'informations concernant cet évènement ou devoir. Les évènements sont précédés par une barre noire et les devoirs par une barre rouge.
</p>
<p>
	Les Journées Éntières d'École, le jour est de couleur verte. Pour les Journées partielles, le nombre de minutes d'école est montré. Si l'école n'est pas ouverte, le jour est de couleur rose.
</p>
<p>
	Si l'école utilise la Rotation de Jours Numérotés, le numéro associé au jour est affiché dans le carré du jour.
</p>
HTML;

endif;


// STUDENTS ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Students/Student.php&include=General_Info&student_id=new'] = <<<HTML
<p>
	<i>Ajouter un Élève</i> vous permet d'ajouter un'élève au système et de l'inscrire.
</p>
<p>
	Pour ajouter l'élève, entrez sa date de naissance, son sexe, son niveau scolaire... Ensuite, sélectionnez la date effective de l'inscription de l'élève et le code d'inscription grâce aux menus déroulants en bas de la page. Si vous souhaitez spécifier un ID pour cet élève, entrez le dans le champ texte "RosarioSIS ID". Si vous laissez ce champ vide, RosarioSIS génèrera un ID élève disponible et l'assignera au nouvel élève. Finalement, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
HTML;

	$help['Students/AddUsers.php'] = <<<HTML
<p>
	<i>Association Parents-Élèves</i> vous permet d'associer des parents aux élèves.
</p>
<p>
	Une fois qu'un compte de parent a été créé, leurs enfants doivent être associés à leur compte grâce à ce programme. Si vous n'avez pas déjà choisi un élève courant, sélectionnez un élève en utilisant l'écran de recherche "Trouver un Élève". Ensuite, cherchez l'utilisateur à associer à cet élève. Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs utilisateurs. Vous pouvez sélectionner tous les utilisateurs de la liste en cochant la case à cocher de l'en-tête de liste. Après avoir sélectionné les utilisateurs désirés dans la liste, cliquez sur le bouton "Ajouter les Parents Sélectionnés" en haut de l'écran.
</p>
<p>
	Une fois l'élève sélectionné, vous pouvez voir les parents déjà associés à cet élève. Ces parents sont listés au-dessus de l'écran de recherche / des résultats de recherche. Ces parents peuvent être dissociés de cet élève en cliquant sur l'icône effacer (-) à côté du parent que vous souhaitez dissocier de l'élève. Il vous sera demandé de confirmer cette action.
</p>
HTML;

	$help['Students/AssignOtherInfo.php'] = <<<HTML
<p>
	<i>Assigner Infos Élève en groupe</i> vous permet d'assigner en une fois à un groupe d'élèves des valeurs aux champs élèves.
</p>
<p>
	Premièrement, recherchez des élèves. Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Vous pouvez sélectionner tous les élèves de la liste en cochant la case à cocher de l'en-tête de liste. Après avoir sélectionné les élèves, remplissez les champs élèves souhaités présents au-dessus de la liste d'élèves. Les champs laissés vides n'affecteront pas les élèves sélectionnés. Une fois les élèves désirés sélectionnés et les champs désirés remplis, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
HTML;

	$help['Students/Letters.php'] = <<<HTML
<p>
	<i>Imprimer des Lettres</i> vous permet d'imprimer des lettres formatées pour un ou plusieurs élèves.
</p>
<p>
	Premièrement, recherchez des élèves. Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Vous pouvez sélectionner tous les élèves de la liste en cochant la case à cocher de l'en-tête de liste. Après avoir sélectionné les élèves, saisissez le texte de la lettre le champ texte "Contenu du Courrier" au-dessus de la liste d'élèves.
</p>
<p>
	Vous pouvez insérer certaines informations des élèves dans la lettre en copiant les variables suivantes:
</p>
<ul>
	<li>
		<b>Nom Complet:</b> __FULL_NAME__
	</li>
	<li>
		<b>Prénom:</b> __FIRST_NAME__
	</li>
	<li>
		<b>Second Prénom:</b> __MIDDLE_NAME__
	</li>
	<li>
		<b>Nom de Famille:</b> __LAST_NAME__
	</li>
	<li>
		<b>ID RosarioSIS:</b> __STUDENT_ID__
	</li>
	<li>
		<b>Niveau Scolaire:</b> __GRADE_ID__
	</li>
</ul>
<p>
	Aussi, vous pouvez choisir d'imprimer des lettres avec des étiquettes d'adresse. Les lettres auront leurauront leur étiquette d'adresse positionnée de manière a être visible dans une enveloppe à fenêtre lorsque la feuille est pliée en trois. Plus d'une lettre peut-être imprimée si l'élève a des tuteurs résidant à différentes adresses.
</p>
<p>
	Les lettres seront automatiquement téléchargées au format PDF pour l'impression lorsque vous cliquez sur le bouton "Imprimer le Courrier pour les Élèves Sélectionnés".
</p>
HTML;

	$help['Students/Informations Générales'] = <<<HTML
<p>
	<i>Informations Générales</i> affiche les informations principales d'un élève. Cela inclut la date de naissance, le numéro de sécurité sociale, l'origine ethnique, le sexe, l'endroit de naissance, et le niveau scolaire. Vous pouvez modifier ces informations en cliquant sur la valeur que vous souhaitez changer, puis en l'éditant et enfin en cliquant sur le bouton "Enregistrer" en haut de la page.
</p>
HTML;

	$help['Students/Adresses & Contacts'] = <<<HTML
<p>
	<i>Adresses &amp; Contacts</i> affiche les informations de contact et l'adresse d'un élève.
</p>
<p>
	Un élève peut avoir une ou plusieurs adresses. Pour ajouter une adresse, cliquez sur le lien "Ajouter une Nouvelle Adresse" et remplissez les champs vides de la partie Adresse. Enfin, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Maintenant, vous pouvez ajouter un contact à cette adresse. Pour ce faire, renseignez le nom du contact, et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Vous pouvez ajouter d'autres informations sur ce contact en cochant les cases à cocher "Garde" et "Urgence ", en cliquant d'abord sur leur valeur par défaut ("Non", croix). Les relations qui ont la "Garde" de l'élève reçoivent les courriers et les relations marquées comme contact d'"Urgence" peuvent être contacté en cas d'urgence.
</p>
<p>
	Vous pouvez ajouter d'autres informations sur ce contact, comme leur numéro de téléphone portable, numéro de fax, profession, lieu de travail, etc. en remplissant le titre de la nouvelle donnée dans le champ "Description" et sa valeur correspondante dans le champ "Valeur".
</p>
<p>
	Les contacts et les informations qui leurs sont associées peuvent être supprimées en cliquant sur l'icône effacer (-) à côté de l'information à supprimer. (Note: vous devrez confirmer les suppressions.) Les informations à l'écran peuvent être modifiées en cliquant d'abord sur l'information, ensuite en changeant sa valeur, et finalement en cliquant sur le bouton "Enregistrer" en haut de l'écran.
</p>
HTML;

	$help['Students/Médical'] = <<<HTML
<p>
	<i>Médical</i> affiche les données médicales d'un élève.
</p>
<p>
	Cela inclut le médecin de l'élève, son téléphone, l'hôpital privilégié de l'élève, les commentaires médicaux, si l'élève à un certificat médical, et les commentaires concernant le certificat médical. Pour modifier une de ces valeurs, cliquez sur la valeur à changer, éditez-la et cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Vous pouvez aussi ajouter des entrées pour chaque vaccination ou visite médicale de l'élève ainsi que pour les alertes médicales comme par exemple les allergies ou les maladies.
</p>
<p>
	Pour ajouter une vaccination, visite médicale ou une alerte médicale, remplissez les champs vides de la liste appropriée, et cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Pour modifier une vaccination, visite médicale ou une alerte médicale, cliquez surla valeur à changer, éditez-la et cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Pour supprimer une vaccination, visite médicale ou une alerte médicale, cliquez sur the l'icône effacer (-) à côté de l'élément que vous souhaitez supprimer. Vous devrez confirmer la suppression.
</p>
HTML;

	$help['Students/Inscription'] = <<<HTML
<p>
	<i>Inscription</i> peut servir à inscrire ou retirer un élève d'une école. Un élève peut avoir au maximum un dossier d'inscripion ouvert à la fois.
</p>
<p>
	Pour retirer un élève, renseignez la date de "Retrait" ainsi que la raison du retrait. Cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Maintenant, vous pouvez réinscrire l'élève. Pour ce faire, sélectionnez la date d'inscription et la raison de son inscription dans la nouvelle ligne en bas de la liste. Aussi, sélectionnez l'école à laquelle l'élève devra être inscrit et cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Les dates d'inscriptions et de retrait, ainsi que les raisons peuvent être modifiées en cliquant sur leurs valeurs, les ajustant, et en cliquant sur le bouton "Enregistrer" en haut de l'écran.
</p>
HTML;

	$help['Students/AdvancedReport.php'] = <<<HTML
<p>
	<i>Rapport Avancé</i> est un outil d'aide à la création de rapport personnalisés.
</p>
<p>
	Sélectionnez ce que vous désirez voir sur le rapport en cochant les cases à cocher à côté des colonnes souhaitées. Ces colonnes apparaîtront dans la liste en haut de l'écran dans l'ordre où vous les avez sélectionnées.
</p>
<p>
	Pour sortir la liste des élèves qui fêtent leur anniversaire à une date donnée, sélectionnez la date en uilisant les menus déroulants "Mois de Naissance" et "Jour de Naissance" présents dans la boîte "Trouver un Élève".
</p>
HTML;

	$help['Students/AddDrop.php'] = <<<HTML
<p>
	<i>Rapport des Ajouts / Retraits</i> est un rapport montrant tous les élèves ayant été inscrits ou retirés durant la période de temps sélectionnée.
</p>
<p>
	Pour consulter une autre période, changez les dates situées dans la partie supérieure de la page et cliquez sur le bouton "Go" à droite des dates.
</p>
HTML;

	$help['Students/MailingLabels.php'] = <<<HTML
<p>
	<i>Imprimer Étiquettes Adresse</i> vous permet de générer des étiquettes d'adresse pour un groupe d'élèves, parents ou familles.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève".
</p>
<p>
	Ensuite, vous pouvez sélectionner à qui vous souhaitez envoyer le courrier. Vous pouvez inclure sur l'étiquette le nom de l'élève: différents formats sont disponibles, comme "Smith, John Peter" (Nom de famille, Prénom, Second prénom) ou "John Smith" (Prénom Nom de famille).
</p>
HTML;

	$help['Students/StudentLabels.php'] = <<<HTML
<p>
	<i>Imprimer Étiquettes Élève</i> vous permet de générer des étiquettes pour les dossiers des Élèves.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève".
</p>
<p>
	Ensuite, sélectionnez les élèves &amp; quoi inclure sur les étiquettes: utilisez les cases à cocher à gauche de chaque élève pour les sélectionner, et les options disponibles dans la section "Inclure sur les Étiquettes" pour choisir les informations à inclure. Vous pouvez choisir d'imprimer le nom de l'élève: différents formats sont disponibles, comme "Smith, John Peter" (Nom de famille, Prénom, Second prénom) ou "John Smith" (Prénom Nom de famille). Vous pouvez aussi inclure le Professeur Référent et la Classe de Présence de l'élève sur l'étiquette du dossier.
</p>
HTML;

	$help['Students/PrintStudentInfo.php'] = <<<HTML
<p>
	<i>Imprimer Infos Élève</i> génère un rapport sur plusieurs pages à partir des informations présentes dans les onglets du programme Informations Élève.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève".
</p>
<p>
	Ensuite, sélectionnez les élèves et quoi inclure sur le rapport: utilisez les cases à cocher à gauche de chaque élève pour les sélectionner, et ensuite, en haut de l'écran, cochez les onglets du programme Informations Élève que vous désirez inclure. Vous pouvez aussi cocher "Étiquettes Adresse" pour ajouter les informations de contact au rapport afin de l'envoyer dans une enveloppe à fenêtre. Lorsque vous êtes prêt, cliquez sur le bouton "Imprimer les Informations pour les Élèves Sélectionnés".
</p>
HTML;

	$help['Custom/MyReport.php'] = <<<HTML
<p>
	<i>Mon Rapport</i> génère un rapport qui peut-être téléchargé au format Excel, contenant les informations de contact.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève".
</p>
<p>
	Ce rapport se compose d'un listing imprimable, ou à proprement parler, d'une feuille de calcul des élèves et leurs informations de contact que vous pouvez utiliser pour constituer un répertoire ou vous aider pour un publipostage, par exemple.
</p>
<p>
	Cliquez sur l'icône de Téléchargement au-dessus de la liste afin d'exporter le rapport au format Excel.
</p>
HTML;

	$help['Students/StudentFields.php'] = $help['Students/PeopleFields.php'] = $help['Students/AddressFields.php'] = <<<HTML
<p>
	Les <i>Champs de Données</i> vous permettent de configurer des champs personnalisés pour votre école. Ces champs servent à sauvegarder des informations concernant les élèves dans les onglets "Infos Générales" / "Adresses &amp; Contacts" ou un onglet personnalisé de l'écran élève.
</p>
<p>
	Catégories de Champs de Données
</p>
<p>
	RosarioSIS vous permet d'ajouter des catégories personnalisées qui prendront la forme de nouveaux "Onglets" de Champs de Données dans le programme Élèves &gt; Informations Élève. Pour créer une nouvelle catégorie ou "onglet", cliquez sur l'icône "+" en dessous des Catégories existantes.
</p>
<p>
	Nouvelle Catégorie
</p>
<p>
	Vous pouvez maintenant taper le nom de la nouvelle Catégorie dans le champ "Titre". Ajoutez un ordre de tri (ordre dans lequel les onglets apparaitront dans le programme Informations Élève), et le nombre de colonnes que l'onglet doit afficher (optionnel). Cliquez sur "Enregistrer" une fois que vous avez fini.
</p>
<p>
	Ajouter un Nouveau Champ
</p>
<p>
	Cliquez sur l'icône "+" en dessous du texte "Aucun(e) Champ Élève". Renseignez les champs Nom du Champ, et choisissez quel type de champ vous souhaitez lui associer grâce au menu déroulant "Type du Champ".
</p>
<ul>
<li>
	Les champs de type "Menu Déroulant" créent des menus depuis lesquels vous pouvez sélectionner une option. Pour créer ce type de champ, cliquez sur "Menu Déroulant" et ajouter les options (une par ligne) dans le champ texte "Menu Déroulant/Menu Déroulant Automatique/Menu Déroulant Codé/Options à Choix Multiple".
</li>
<li>
	Les champs de type "Auto Menu Déroulant" créent des menus depuis lesquels vous pouvez sélectionner une option, et ajouter des options. Vous ajoutez des options en sélectionnant l'option "-Modifier-" dans les choix du menu et cliquez sur "Enregistrer". Vous pouvez dès lors modifier le champ en enlevant le texte "-Modifier-" en rouge du champ, et en entrant l'information correcte. RosarioSIS récupère toutes les options qui ont été ajoutées à ce champ au moment de créer le menu déroulant.
</li>
<li>
	Les champs de type "Menu Déroulant Modifiable" sont similaires aux champs Menu Déroulant Automatique.
</li>
<li>
	Les champs de type "Menu Déroulant Codé" sont créés en ajoutant les options au champ texte long en respectant le modèle suivant: "option affichée"|"option sauvegardée en base" (où | est le caractère "barre verticale"). Par exemple: "Deux|2", où "Deux" est affiché à l'utilisateur, ou dans une feuille de calcul téléchargée, et "2" est sauvegardé en base de données.
</li>
<li>
	Les champs de type "Menu Déroulant Exportable" sont créés en ajoutant les options au champ texte long en respectant le même modèle utilisé pour les champs "Menu Déroulant Codé" ("option affichée"|"option sauvegardée en base"). Par exemple: "Deux|2", où "Deux" est affiché à l'utilisateur, et "2" est la valeur dans une feuille de calcul téléchargée, mais "Deux" est sauvegardé en base de données.
</li>
<li>
	Les champs de type "Options à Choix Multiple" créent des cases à cocher multiples afin de pouvoir choisir une ou plusieurs options.
</li>
<li>
	Les champs de type "Texte" créent un champ texte alphanumérique ayant une capacité maximum de 255 caractères.
</li>
<li>
	Les champs de type "Texte Long" créent un grand champ de texte alphanumérique pouvant recevoir un maximum de 5000 caractères.
</li>
<li>
	Les champs de type "Case à Cocher" créent des cases à cocher. Lorsque elle est cochée sa signification est "oui", et décochée, sa signification est "non".
</li>
<li>
	Les champs de type "Nombre" créent des champs texte qui acceptent seulement des valeurs numériques.
</li>
<li>
	Les champs de type "Date" créent des menus déroulants afin de pouvoir sélectionner un date.
</li>
</ul>
<p>
	La case à cocher "Obligatoire", si cochée, rendra le champ obligatoire de façon que si le champ est laissé vide au moment de sauvegarder la page, une erreur sera affichée.
</p>
<p>
	L'"Ordre de Tri" détermine l'ordre dans lequel les champs sont affichés dans l'onglet des Informations Élève.
</p>
<p>
	Supprimer un champ
</p>
<p>
	Vous pouvez supprimer n'importe quel champ Élève ou Catégorie en cliquant sur le bouton "Effacer" dans le coin supérieur droit. Veuillez noter que vous perdrez toutes vos données liées au champ si vous supprimez un champ ou une catégorie déjà en usage.
</p>
HTML;

	$help['Students/EnrollmentCodes.php'] = <<<HTML
<p>
	<i>Codes d'Inscription Élèves</i> vous permet de configurer les codes d'inscription élèves de votre école. Les codes d'inscription élèves sont utilisés dans l'écran d'Inscription élève, et spécifient la raison pour laquelle l'élève est inscrit ou retiré d'une école. Ces codes s'appliquent à toutes les écoles du système.
</p>
<p>
	La colonne "Défaut pour Report Final" définit le code utilisé pour inscrire les élèves pour l'année scolaire suivante au moment du Report Final. Il devrait y avoir exactement un code d'inscription par Défaut pour le Report Final (de type "Ajouter").
</p>
<p>
	Pour ajouter un code d'inscription, remplissez les champs titre, nom abrégé, et type de la dernière ligne de la liste des codes d'inscription élèves. Cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour modifier un code d'inscription, cliquez sur une information du code d'inscription, éditez la valeur, et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour supprimer un code d'inscription, cliquez sur l'icône effacer (-) à côté du code d'inscription que vous souhaitez supprimer. Vous devrez confirmer la suppression.
</p>
HTML;

	// Enseignant & Parent & Élève.
else :

	$help['Students/Informations Générales'] = <<<HTML
<p>
	<i>Informations Générales</i> affiche les informations principales d'un élève. Cela inclut la date de naissance, le numéro de sécurité sociale, l'origine ethnique, le sexe, l'endroit de naissance, et le niveau scolaire.
</p>
HTML;

	$help['Students/Adresses & Contacts'] = <<<HTML
<p>
	<i>Adresses &amp; Contacts</i> affiche l'adresse et les informations de contact d'un élève.
</p>
<p>
	Un élève peut avoir une ou plusieurs adresses associées.
</p>
HTML;

	$help['Students/Enrollment'] = <<<HTML
<p>
	<i>Inscription</i> affiche l'historique des inscriptions d'un élève.
</p>
HTML;

	$help['Custom/Registration.php'] = <<<HTML
<p>
	<i>Inscription</i> vous permet d'enregister les informations de contact de l'élève.
</p>
<p>
	Remplissez les champs du formulaire avec les informations de contact et les adresses associées. Ensuite, saisissez ou actualisez les informations de l'élève.
</p>
<p>
	Une fois le formulaire complété, cliquez sur le bouton "Enregistrer" en bas de l'écran.
</p>
HTML;

endif;


// USERS ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Users/User.php'] = <<<HTML
<p>
	<i>Informations Générales</i> affiche les informations principales d'un utilisateur. Cela inclut son nom, nom d'utilisateur, mot de passe, profil, école, adresse email, et numéro de téléphone. Si vous êtes un administrateur, vous pouvez changer ces informations en cliquant sur la valeur souhaitée, la modifier, et cliquer sur le bouton "Enregistrer" en haut de la page. Vous pouvez supprimer un utilisateur en cliquant sur le bouton "Effacer" en haut de l'écran et en confirmant l'action. Note: vous ne devriez jamais supprimer un enseignant après qu'il ait donné ne serait-ce qu'un cours, les informations de l'utilisateur devant rester afin que le nom de l'enseignant puisse apparaître correctement sur les livrets scolaires.
</p>
HTML;

	$help['Users/User.php&staff_id=new'] = <<<HTML
<p>
	<i>Ajouter un Utilisateur</i> vous permet d'ajouter un utilisateur au système. Cela inclut les administrateurs, les enseignants, et les parents. Remplissez les champs nom, nom d'utilisateur, mot de passe, profil, école, adresse email, et numéro de téléphone de l'utilisateur. Cliquez sur le bouton "Enregistrer".
</p>
HTML;

	$help['Users/AddStudents.php'] = <<<HTML
<p>
	<i>Association Élèves-Parents</i> vous permet d'associer des élèves aux parents.
</p>
<p>
	Une fois qu'un compte parent a été créé, leurs enfants doivent être associés à leur compte avec ce programme. Si vous n'avez pas déjà choisi un utilisateur courant durant votre session, sélectionnez un utilisateur en utilisant l'écran de recherche "Trouver un Utilisateur". Ensuite, cherchez un élève à ajouter au compte de l'utilisateur. Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Vous pouvez sélectionner tous les élèves de la liste en cochant la case à cocher de l'en-tête de liste. Une fois les élèves désirés sélectionnés, cliquez sur le bouton "Ajouter les Élèves Sélectionnés" en haut de l'écran.
</p>
<p>
	Après avoir sélectionné un utilisateur, vous pouvez voir les élèves déjà associés à cet utilisateur. Ces élèves sont listés en haut de l'écran de recherche / des résultats de recherche. Ces élèves peuvent être dissociés de l'utilisateur en cliquant sur l'icône effacer (-) à côté de l'élève que vous souhaitez dissocier. Il vous sera demandé de confirmer cette action.
</p>
HTML;

	$help['Users/Preferences.php'] = <<<HTML
<p>
	<i>Mes Préférences</i> vous permet de personnaliser RosarioSIS selon vos besoins. Vous pouvez aussi changer votre mot de passe, et configurer RosarioSIS pour afficher les données qui sont importantes pour votre travail.
</p>
<p>
	Onglet Options d'Affichage
</p>
<p>
	Elles vous permettent de sélectionnez votre thème préféré pour RosarioSIS. Vous pouvez changer le thème (palette de couleurs globale) ou, au sein d'un thème particulier, la Couleur de Sélection. Vous pouvez aussi définir le format de date, comme par exemple changer le mois pour "Janvier", ou "Jan" ou "01". "Désactiver les alertes de connexion" cachera les alertes affichées sur le Portail (première page après le login), comme les absences non renseignées des Enseignants, les nouveaux Incidents de discipline &amp; les alertes du Solde Compte Cantine.
</p>
<p>
	Onglet Liste d'Élèves
</p>
<p>
	"Tri Élèves" vous permet d'avoir les élèves dans les listes triés selon seulement leur "Nom" ou selon à la fois leur Niveau Scolaire et leur Nom. "Type de Fichier d'Export" vous perment de choisir entre des fichiers limités par tabulations, pour Excel, des fichiers CSV (comma-separated values), pour LibreOffice et des fichiers XML. "Format des Dates pour l'Export" vous perment de choisir divers formats de dates utilisés lors de l'export de champs date. "Afficher l'écran de recherche d'élève" devrait rester coché, sauf instruction contraire.
</p>
<p>
	Onglet Mot de Passe
</p>
<p>
	Il vous aidera a modifier votre mot de passe. Entrez simplement votre mot de passe actuel dans le premier champ texte, et votre nouveau mot de passe dans les deux champ textes suivants. Finalement, cliquez sur "Enregistrer".
</p>
<p>
	Onglet Champs Élève
</p>
<p>
	Les deux colonnes sur le coté droit de la page vous permettent de choisir les champs de données à afficher soit sur la page "Trouver un Élève" ou lorsque vous cliquez sur "Affichage Complet" au-dessus des listing d'élèves. Cliquez sur la case à cocher "Recherche" pour ajouter un champ que vous cherchez souvent à la page "Trouver un Élève", au lieu d'avoir à cliquer sur "Recherche Avancée" pour pouvoir utiliser ce champ. Cliquez sur la case à cocher "Affichage Complet" ajoute ce champ à votre rapport Affichage Complet. Vous pouvez ajouter ou retirer des champs aussi souvent que vous voulez, permettant ainsi de personnaliser la page de Recherche et le rapport Affichage Complet.
</p>
HTML;

	$help['Users/Profiles.php'] = <<<HTML
<p>
	<i>Profils Utilisateur</i> aide à la configuration des droits d'accès aux informations des utilisateurs et s'ils peuvent les modifier.
</p>
<p>
	RosarioSIS vient avec quatre groupes ou profils prédéfinis: Administrateur, Enseignant, Parent &amp; Élève. Le profil Administrateur a le plus de droits, tandis que les autres prodils sont restreints de manière appropriée. Veuillez noter que les enseignants sont limités en accès aux élèves de leurs classes seulement, et que les parents peuvent seulement voir les informations de leurs enfants. Les élèves, quant à eux ne peuvent voir que leurs informations personelles.
</p>
<p>
	Si vous cliquez sur un des Profils, vous verrez la page des Droits d'Accès. Cette page montre à quelles pages ou programmes le profil a accès en LECTURE (Peut Utiliser) ou en ÉCRITURE (Peut Modifier) à l'information de cette page particulière.
</p>
<p>
	Lorsque vous décochez "Peut Modifier", les utilisateurs appartenant à ce profil pourront accéder au programme dans le menu et verront les informations de la page en cliquant sur celui-ci. Ils ne seront PAS capables de modifier les informations présentes sur cette page. Lorsque vous décochez "Petu Utiliser" pour un programme particulier, les utilisateurs appartenant à ce profil ne verront plus le programme dans le menu et ne pourront plus y accéder.
</p>
<p>
	Profil Administrateur
</p>
<p>
	Les Administrateurs ont accès à quasiment toutes les pages, en lecture et en écriture. Par défaut, ils ne peuvent pas voir l'onglet "Commentaires" du programme Informations Élève, mais ils peuvent accéder à et modifier toutes les autres pages.
</p>
<p>
	Il est possible de restreindre l'édition des profils utilisateurs en cochant la case <i>Utilisateurs > Informations Utilisateur > Informations Générales > Profil Utilisateur</i>. Les administrateurs perdent alors la possibilité d'assigner le profil des utilisateurs (et les droits d'accès).
</p>
<p>
	Il est possible de restreindre l'édition des écoles des utilisateurs en cochant la case <i>Utilisateurs > Informations Utilisateur > Informations Générales > Écoles</i>. Les administrateurs perdent alors la possibilité d'ajouter ou d'enlever des écoles à/d'un utilisateur.
</p>
<p>
	Profil Enseignant
</p>
<p>
	Les Enseignants ont le droit d'accéder à un nombre de pages plus restreint au sein de RosarioSIS, et leur capacité à modifier ces pages est encore plus restreinte. Par défaut, les enseignants ne peuvent pas changer les informations d'un élève EXCEPTÉ pour l'onglet Commentaires.
</p>
<p>
	Profil Parent
</p>
<p>
	Les Parents sont encore plus restreints. Les Parents ont seulement accès aux informations qui sont de leur intérêt propre, les informations de leurs enfants, les présences et notes.
</p>
<p>
	Ajouter un Profil Utilisateur
</p>
<p>
	Pour des raisons de sécurité, il est recommandé d'ajouter un profil "admin" de type "Administrateur" en vue de limiter les droits des administrateurs. Il n'est en effet pas nécessaire que TOUS les administrateurs soient capables d'Ajouter des Écoles, Copier des Écoles, modifier les Périodes Scolaires, ou bien changer les Échelles de Notation, etc. Une fois la configuration d'une école terminée, les changements apportés à la configuration par des utilisateurs non-avertis peuvent-être une source de problèmes et de dysfonctionnements.
</p>
<p>
	Pour ajouter un nouveau Profil, entrez son nom dans le champ texte "Titre" et ensuite sélectionnez-le "Type" de profil. Finalement, cliquez sur le bouton "Enregistrer" situé dans la partie supérieure de l'écran.
</p>
<p>
	Ajuster les Droits d'accès
</p>
<p>
	Afin de mieux configurer les droits que vos utilisateurs auront, il peut-être pratique d'entrer dans RosarioSIS avec un utilisateur de test appartenant au profil et de se rendre compte de ce qui peut-être vu. Less is more!
</p>
HTML;

	$help['Users/Exceptions.php'] = <<<HTML
<p>
	<i>Droits d'Accès</i> vous permet de définir des privilèges d'accès ou de modification pour n'importe quel programme et pour un certain utilisateur.
</p>
<p>
	Afin d'ajuster les privilèges d'un utilisateur, premièrement, cherchez un utilisateur et sélectionnez-le en cliquant sur son nom dans la liste de résultats. Ensuite, utilisez les cases à cocher pour définir à quels programmes l'utilisateur aura accès, et pour lesquels il aura le droit de modification. Si un utilisateur ne peut pas utiliser un programme particulier, ce programme ne sera pas affiché dans son menu. Si au contraire, il peut l'utiliser, mais ne peut pas modifier les informations, le programme montrera les informations mais ne le laissera pas les modifier. Une fois les cases appropriées cochées, cliquez sur le bouton "Enregistrer" pour sauvegarder les droits de l'utilisateur.
</p>
HTML;

	$help['Users/UserFields.php'] = <<<HTML
<p>
	<i>Champs Utilisateur</i> vous permet d'ajouter de nouveaux champs et onglets au programme Informations Utilisateur.
</p>
<p>
	Catégories de Champ Utilisateur
</p>
<p>
	RosarioSIS vous permet d'ajouter des catégories personnalisées qui prendront la forme de nouveaux "onglets" de Champs Utilisateur dans le programme Utilisateurs &gt; Informations Utilisateur. Pour créer une nouvelle catégorie ou "onglet", cliquez sur l'icône "+" en dessous des Catégories existentes.
</p>
<p>
	Nouvelle Catégorie
</p>
<p>
	Vous pouvez entrer les nom de la Catégorie dans le champ "Titre". Ajoutez un ordre de tri (ordre dans lequel les onglets apparaîtront dans le programme Informations Utilisateur), ainsi que le numéro de colonne que l'onglet affichera (optionnel). Cliquez sur "Enregistrer" une fois prêt.
</p>
<p>
	Ajouter un nouveau Champ
</p>
<p>
	Cliquez sur l'icône "+" en dessous du texte "Aucun Champs Utilisateur.". Renseignez le nom du champ, et choisissez quel type de champ vous souhaitez lui associer grâce au menu déroulant "Type du Champ".
</p>
<ul>
<li>
	Les champs de type "Menu Déroulant" créent des menus depuis lesquels vous pouvez sélectionner une option. Pour créer ce type de champ, cliquez sur "Menu Déroulant" et ajouter les options (une par ligne) dans le champ texte "Menu Déroulant/Menu Déroulant Automatique/Menu Déroulant Codé/Options à Choix Multiple".
</li>
<li>
	Les champs de type "Auto Menu Déroulant" créent des menus depuis lesquels vous pouvez sélectionner une option, et ajouter des options. Vous ajoutez des options en sélectionnant l'option "-Modifier-" dans les choix du menu et cliquez sur "Enregistrer". Vous pouvez dès lors modifier le champ en enlevant le texte "-Modifier-" en rouge du champ, et en entrant l'information correcte. RosarioSIS récupère toutes les options qui ont été ajoutées à ce champ au moment de créer le menu déroulant.
</li>
<li>
	Les champs de type "Menu Déroulant Modifiable" sont similaires aux champs Menu Déroulant Automatique.
</li>
<li>
	Les champs de type "Menu Déroulant Codé" sont créés en ajoutant les options au champ texte long en respectant le modèle suivant: "option affichée"|"option sauvegardée en base" (où | est le caractère "barre verticale"). Par exemple: "Deux|2", où "Deux" est affiché à l'utilisateur, ou dans une feuille de calcul téléchargée, et "2" est sauvegardé en base de données.
</li>
<li>
	Les champs de type "Menu Déroulant Exportable" sont créés en ajoutant les options au champ texte long en respectant le même modèle utilisé pour les champs "Menu Déroulant Codé" ("option affichée"|"option sauvegardée en base"). Par exemple: "Deux|2", où "Deux" est affiché à l'utilisateur, et "2" est la valeur dans une feuille de calcul téléchargée, mais "Deux" est sauvegardé en base de données.
</li>
<li>
	Les champs de type "Options à Choix Multiple" créent des cases à cocher multiples afin de pouvoir choisir une ou plusieurs options.
</li>
<li>
	Les champs de type "Texte" créent un champ texte alphanumérique ayant une capacité maximum de 255 caractères.
</li>
<li>
	Les champs de type "Texte Long" créent un grand champ de texte alphanumérique pouvant recevoir un maximum de 5000 caractères.
</li>
<li>
	Les champs de type "Case à Cocher" créent des cases à cocher. Lorsque elle est cochée sa signification est "oui", et décochée, sa signification est "non".
</li>
<li>
	Les champs de type "Nombre" créent des champs texte qui acceptent seulement des valeurs numériques.
</li>
<li>
	Les champs de type "Date" créent des menus déroulants afin de pouvoir sélectionner un date.
</li>
</ul>
<p>
	La case à cocher "Obligatoire", si cochée, rendra le champ obligatoire de façon que si le champ est laissé vide au moment de sauvegarder la page, une erreur sera affichée.
</p>
<p>
	L'"Ordre de Tri" détermine l'ordre dans lequel les champs sont affichés dans l'onglet des Informations Utilisateur.
</p>
<p>
	Supprimer un champ
</p>
<p>
	Vous pouvez supprimer n'importe quel champ Utilisateur ou Catégorie en cliquant sur le bouton "Effacer" dans le coin supérieur droit. Veuillez noter que vous perdrez toutes vos données liées au champ si vous supprimez un champ ou une catégorie déjà en usage.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Grades/InputFinalGrades.php'] = <<<HTML
<p>
	<i>Programmes Enseignants: Saisie des Notes</i> vous permet d'enter les notes du trimestre, semestre ou de la période intermédiaire pour tous les élèves de l'enseignant sélectionné dans la classe courante. Par défaut, ce programme liste les élèves de la première classe de l'enseignant sélectionné pour le trimestre courant. Vous pouvez changer la classe grâce au menu déroulant en haut de l'écran. Aussi, vous pouvez changer de trimestre en sélectionnant une autre période scolaire grâce au menu déroulant du menu latéral. Enfin, vous pouvez sélectionner le semestre ou la période intermédiaire courant(e) en changeant la période scolaire du menu déroulant en haut de l'écran.
</p>
<p>
	Une fois dans la bonne période scolaire, vous pouvez saisir les notes des élèves en sélectionnant la note de chaque élève et en entrant les commentaires. Une fois que toutes les notes et commentaires ont été entrés, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Si l'enseignant sélectionné utilise le Carnet de Notes, RosarioSIS peut calculer les notes de chaque élève en cliquant sur le lien "Utiliser les Notes du Carnet" au-dessus de la liste. En cliquant sur le lien, les notes de chaque élève sont enregistrées et la liste rafraîchie.
</p>
<p>
	Si la période scolaire dans laquelle vous êtes est une Période Intermédiaire, lorsque vous cliquez sur le lien "Utiliser les Notes du Carnet", les notes prises en compte seront limitées aux Devoirs dont la Date de Rendu est comprise dans la Période intermédiaire, ou ceux qui n'ont pas de Date de Rendu.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Grades/Grades.php'] = <<<HTML
<p>
	<i>Programmes Enseignants: Notes du Carnet</i> vous permet de consulter et modifier les notes du carnet des élèves. Vous pouvez sélectionner les classes de l'enseignant en utilisant le menu déroulant du coin supérieur gauche de la page. Le Carnet de Notes Notes de la classe sera affiché. Comme administrateur, vous pouvez sélectionner un élève particulier, ou les totaux pour une catégorie de devoirs, ou bien tous les élèves pour un ou tous les devoirs. Le menu déroulant "Tous" vous permet de sélectionner une catégorie de devoirs, ou alternativement vous pouvez utiliser les onglets au-dessus du listing des notes. Le menu déroulant "Totaux" vous permet de sélectionner un devoir particulier ou le "total" de tous les devoirs.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Grades/AnomalousGrades.php'] = <<<HTML
<p>
	<i>Programmes Enseignants: Notes non Conformes</i> est un rapport qui aide les enseignants à repérer les notes manquantes, non conformes ou les dispenses. Les notes apparaissant dans ce rapport ne sont PAS problématiques, mais un enseignant PEUT vouloir les réviser. Les notes manquantes, négatives et les dispenses, ou les notes qui sont des Points Bonus ou celles qui excèdent les 100% sont affichées. La colonne "Problème" indique la raison pour laquelle la note est non conforme.
</p>
<p>
	Vous pouvez sélectionner les classes d'un enseignant en utilisant le menu déroulant du coin supérieur gauche de la page. Vous pouvez aussi sélectionnez quel type de notes "non conformes" que vous souhaitez que le rapport affiche.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Attendance/TakeAttendance.php'] = <<<HTML
<p>
	<i>Programmes Enseignants: Saisir les Absences</i> vous permet de saisir les absences à une classe ou pour tous les élèves de l'enseignant. Par défaut, le programme listera les élèves de la classe courante. Vous pouvez changer la classe courante au moyen du menu déroulant situé en haut de l'écran.
</p>
<p>
	Une fois la classe correcte sélectionnée, vous pouvez prendre les présences pour chaque élève. Une fois que vous aurez saisi les absences pour tous les élèves, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php'] = <<<HTML
<p>
	<i>Programmes Enseignants: Saisir l'Éligibilité</i> vous permet d'enter les notes d'éligibilité pour tous les élèves de l'enseignant sélectionné. Par défaut, le programme listera les élèves de la classe courante. Vous pouvez changer la classe courante au moyen du menu déroulant situé en haut de l'écran.
</p>
<p>
	Une fois la classe désirée sélectionnée, vous pouvez enter les notes d'éligibilité en sélectionnant le code d'éligibilité correspondant à chaque élève. Une fois que vous aurez entré l'éligibilité pour tous les élèves, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Si l'enseignant sélectionné utilise le Carnet de Notes, RosarioSIS peut calculer automatiquement les notes d'éligibilité de chaque élève en cliquant sur le lien "Utiliser les Notes du Carnet" au-dessus de la liste. En cliquant sur le lien, les notes d'éligibilité de chaque élève sont enregistrées et la liste rafraîchie.
</p>
<p>
	Vous devez saisir l'éligibilité chaque semaine durant la période de temps définie par l'administration de l'école.
</p>
HTML;

endif;


// SCHEDULING ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Scheduling/Schedule.php'] = <<<HTML
<p>
	<i>Emploi du Temps Élève</i> vous permet de modifier l'emploi du temps d'un élève.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève". Vous pouvez rechercher des élèves qui ont demandé un cours spécifique en cliquant sur le lien "Choisir" à côté des options de recherche "Cours" et "Demande" respectivement et en choisissant un cours depuis la fenêtre popup qui apparaît.
</p>
<p>
	Pour ajouter un cours à l'emploi du temps d'un élève, cliquez sur le lien "Ajouter un Cours" à côté de l'icône ajouter (+) et sélectionnez un cours depuis la fenêtre popup qui apparaît. L'écran sera automatiquement rafraîchi et montrera le cours ajouté.
</p>
<p>
	Pour retirer un cours existant, sélectionnez la date de "Retrait" à côté du cours souhaité dans l'emploi du temps de l'élève.
	Si vous sélectionnez une date de "Retrait" antérieure à la date d'"Inscription", le cours sera supprimé et vous devrez confirmer ou non la suppression des absences et notes associées.
</p>
<p>
	Pour changer la classe du cours pour un élève, cliquez sur le texte "Période - Enseignant" du cours que vous souhaitez changer et sélectionnez la nouvelle classe. Vous pouvez aussi changer le trimestre de la même manière.
</p>
<p>
	Tous les suppressions et modifications ne sont pas sauvegardées tant que vous ne cliquez pas sur le bouton "Enregistrer" en haut de l'écran.
</p>
HTML;

	$help['Scheduling/Requests.php'] = <<<HTML
<p>
	<i>Demandes Élève</i> vous permet de spécifier quels cours un élève souhaite suivre pour l'année scolaire suivante. Ces demandes sont utilisées par le Planificateur au moment de remplir un emploi du temps élève.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève". Vous pouvez rechercher des élèves qui ont demandé un cours en particulier en cliquant sur le lien "Choisir" à côté de "Demande" et en choisissant un cours depuis la fenêtre popup qui apparaît.
</p>
<p>
	Vous pouvez ajouter une demande en sélectionnant le cours désiré depuis les options apparaissant en dessous une fois la matière choisie. Vous pouvez ajouter plusieurs demandes pour la même matière, ou bien ajouter une demande en sélectionnant la matière dans la dernière ligne de la liste contenant l'icône ajouter (+). Ce faisant, une autre liste déroulante de cours apparaîtra en dessous de la matière. Une fois toutes les demandes ajoutées, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Lorsque vous enregistrer les demandes d'un élève, le programme Demandes Élève va lancer le programme Planificateur sans sauver l'emploi du temps de l'élève courant afin de vous avertir d'éventuels conflits. Le résultat du Planificateur vous dira aussi si les cours demandés n'ont plus de places disponibles. Si une demande ne peut pas être satisfaite, vous pouvez changer la demande afin d'assurer la bonne planification. Vous pourrez aussi ajouter les cours à l'élève selon les demandes entrées.
</p>
<p>
	Enfin, une fois les demandes d'un élève enregistrées, vous pourez spécifier un enseignant ou une tranche horaire (Période) ou bien exclure un enseignant ou une tranche horaire (Période). Pour ce faire, sélectionnez l'enseignant ou la tranche horaire (Période) depuis les menus déroulants respectifs "Avec" et "Sans". Une fois les modifications désirées effectuées, cliquez sur le bouton "Enregistrer". Vous pouvez aussi supprimer une demande en cliquant sur l'icône effacer (-).
</p>
HTML;

	$help['Scheduling/MassSchedule.php'] = <<<HTML
<p>
	<i>Emplois du Temps Groupés</i> vous permet d'inscrire un groupe d'élèves à un ou plusieurs cours en une fois.
</p>
<p>
	Vous devez d'abord sélectionner un (groupe d') élève(s) en utilisant l'écran de recherche "Trouver un Élève". Vous pouvez rechercher des élèves qui ont demandé un cours spécifique en cliquant sur le lien "Choisir" à côté des options de recherche "Cours" et "Demande" respectivement et en choisissant un cours depuis la fenêtre popup qui apparaît.
</p>
<p>
	Sélectionnez la classe à ajouter en cliquant sur le lien "Choisir un Cours" en haut de l'écran et en choisissant le cours dans la fenêtre popup qui apparaît. La fenêtre se fermera et la classe sélectionnée est alors affichée sur la page.
</p>
<p>
	Répéter au besoin l'opération afin de sélectionner et ajouter une classe supplémentaire.
</p>
<p>
	Ensuite, sélectionnez la "Date de Début" appropriée (la date du premier jour de classe), et la "Période Scolaire" appropriée.
</p>
<p>
	Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Pour sélectionner tous les élèves de la liste, cochez la case à cocher de l'en-tête de liste. Une fois les élèves désirés sélectionnés, cliquez sur le bouton "Ajouter les Cours aux Élèves Sélectionnés" en haut de l'écran.
</p>
HTML;

	$help['Scheduling/MassRequests.php'] = <<<HTML
<p>
	<i>Demandes Groupées</i> vous permet d'ajouter une demande à un groupe d'élèves en une fois.
</p>
<p>
	Vous devez d'abord sélectionner un (groupe d') élève(s) en utilisant l'écran de recherche "Trouver un Élève". Vous pouvez rechercher des élèves qui ont demandé un cours en particulier en cliquant sur le lien "Choisir" à côté de "Demande" et en choisissant un cours depuis la fenêtre popup qui apparaît. Notez que vous pouvez rechercher des élèves qui ont déjà demandé un certain cours ou qui participent à une certaine activité. Ce peut-être utile par exemple pour ajouter une demande pour le cours de laboratoire à tous les élèves ayant demandé chimie. Ou bien vous pouvez ajouter une demande pour le cours d'éducation physique à tous les élèves du club de basketball.
</p>
<p>
	Sélectionnez un cours à ajouter comme demande en cliquant sur le lien "Choisir un Cours" en haut de l'écran et en choisissant le cours dans la fenêtre popup qui apparaît.
</p>
<p>
	Ensuite, sélectionnez l'Enseignant approprié grâce aux menus déroulants "Avec" ou "Sans", et la Tranche Horaire correcte.
</p>
<p>
	Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Pour sélectionner tous les élèves de la liste, cochez la case à cocher de l'en-tête de liste. Une fois les élèves désirés sélectionnés, cliquez sur le bouton "Ajouter la Demande pour les Élèves Sélectionnés" en haut de l'écran. Si vous n'avez pas encore choisi un cours, vous devez le faire avant de cliquer sur le bouton.
</p>
HTML;

	$help['Scheduling/MassDrops.php'] = <<<HTML
<p>
	<i>Retraits Groupés</i> vous permet de retirer un groupe d'élèves d'un cours en une fois.
</p>
<p>
	Vous devez d'abord sélectionner un (groupe d') élève(s) en utilisant l'écran de recherche "Trouver un Élève". Vous pouvez rechercher des élèves qui ont demandé un cours spécifique en cliquant sur le lien "Choisir" à côté des options de recherche "Cours" et "Demande" respectivement et en choisissant un cours depuis la fenêtre popup qui apparaît.
</p>
<p>
	Sélectionnez une classe à retirer en cliquant sur le lien "Choisir un Cours" en haut de l'écran et en choisissant le cours dans la fenêtre popup qui apparaît. La fenêtre se fermera et la classe sélectionnée est alors affichée sur la page.
</p>
<p>
	Ensuite, sélectionnez la "Date de Retrait" appropriée (la date à laquelle les élèves sont retirés de cette classe), et la "Période Scolaire" appropriée.
</p>
<p>
	Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Pour sélectionner tous les élèves de la liste, cochez la case à cocher de l'en-tête de liste. Une fois les élèves désirés sélectionnés, cliquez sur le bouton "Retirer le Cours pour les Élèves Sélectionnés" en haut de l'écran.
</p>
HTML;

	$help['Scheduling/PrintSchedules.php'] = <<<HTML
<p>
	<i>Imprimer Emplois du Temps</i> est un utilitaire qui vous permet d'imprimer les emplois du temps d'un ou plusieurs élèves.
</p>
<p>
	Vous pouvez rechercher des élèves qui ont sont inscrits ou ont demandé un cours spécifique en cliquant sur le lien "Choisir" à côté des options de recherche "Cours" et "Demande" respectivement et en choisissant un cours depuis la fenêtre popup qui apparaît.
</p>
<p>
	Aussi, vous pouvez choisir d'imprimer les emplois du temps avec des étiquettes d'adresse. Les emplois du temps auront leur étiquette d'adresse positionnée de manière a être visible dans une enveloppe à fenêtre lorsque la feuille est pliée en trois. Plus d'un emploi du temps peut-être imprimé par si l'élève a des tuteurs résidant à différentes adresses.
</p>
<p>
	Les emplois du temps seront automatiquement téléchargés au format PDF pour l'impression lorsque vous cliquez sur le bouton "Créer les Emplois du Temps pour les Élèves Sélectionnés".
</p>
HTML;

	$help['Scheduling/PrintClassLists.php'] = <<<HTML
<p>
	<i>Imprimer Listes de Classe</i> vous permettra d'imprimer un rapport détaillé sur les élèves et par classe. Vous pouvez éventuellement préciser un Enseignant ou une Matière ou une Tranche horaire ou encore une Classe spécifique.
</p>
<p>
	Premièrement, sélectionnez la ou les Classes
</p>
<p>
	Sélectionner un "Enseignant" montrera toutes les classes de cet enseignant. Sélectionner une "Matière" montrera toutes les classes de cette matière. Sélectionner une "Tranche horaire" montrera toutes les classes de cette tranche horaire. Sélectionner un "Cours" via le lien "Choisir" montrera cette classe précise.
</p>
<p>
	Ensuite, sur le côté gauche de la page, cochz les colonnes que souhaitez voir sur le rapport. Les champs apparaîtront, dans l'ordre où vous les avez sélectionné, dans une liste en haut de la page.
</p>
<p>
	Enfin, sélectionnez les Classes à Lister sur le rapport en bas de la page et cliquez sur le bouton "Créer les Listes de Classe pour les Classes Sélectionnées".
</p>
<p>
	Les Listes de Classe contenant les colonnes sélectionnées seront générées au format PDF pour l'impression ou l'envoi par email.
</p>
HTML;

	$help['Scheduling/PrintRequests.php'] = <<<HTML
<p>
	<i>Imprimer les Demandes</i> est un outil qui vous permet d'imprimer les demandes d'un ou plusieurs élèves.
</p>
<p>
	Vous pouvez rechercher les élèves inscrits à un cours spécifique en cliquant sur le lien "Choisir" à côté de l'option de recherche "Cours" et en choisissant un cours depuis la fenêtre popup qui apparaît.
</p>
<p>
	Aussi, vous pouvez choisir d'imprimer les demandes avec des étiquettes d'adresse. Les feuilles de demandes auront leur étiquette d'adresse positionnée de manière a être visible dans une enveloppe à fenêtre lorsque la feuille est pliée en trois. Plus d'une feuille de demandes peut-être imprimée si l'élève a des tuteurs résidant à différentes adresses.
</p>
<p>
	Les feuilles de demandes seront automatiquement téléchargées au format PDF pour l'impression lorsque vous cliquez sur le bouton "Valider".
</p>
HTML;

	$help['Scheduling/ScheduleReport.php'] = <<<HTML
<p>
	<i>Rapport Emploi du Temps</i> est un rapport qui permet de consulter les inscriptions des élèves pour chaque cours, les élèves qui ont demandé le cours mais qui n'ont pas pu y être inscrit, et le nombre de demandes, places disponibles et places totales pour chaque cours.
</p>
<p>
	Afin de naviguer au sein de ce rapport, cliquez d'abord sur une des matières. Vous verrez alors chaque cours de cette matière ainsi que le nombre de demandes, places disponibles et places totales pour chaque cours. Si vous choisissez un cours en cliquant dessus, vous verrez une liste des classes, le nombre de demandes, places disponibles et places totales. Ici, vous pourrez consulter aussi la liste des élèves inscrits au cours ou la liste des élèves qui ont demandé les cours mais qui ne sont pas inscrits en cliquant sur les liens "Lister les Élèves" et "Lister les Élèves non planifiés", respectivement.
</p>
<p>
	A n'importe quel moment après avoir sélectionné une matière, vous pouvez revenir en arrière en cliquant sur les liens présents en haut de l'écran.
</p>
HTML;

	$help['Scheduling/RequestsReport.php'] = <<<HTML
<p>
	<i>Rapport des Demandes</i> est un rapport qui permet de consulter le nombre d'élèves ayant demandé un cours et le nombre total de places pour ce cours. Les cours sont groupés par matière.
</p>
<p>
	Ce rapport est utile pour créer l'emploi du temps général puisqu'il aide à déterminer le nombre de classes nécessaires pour chaque cours au vu du nombre de demandes pour le cours.
</p>
HTML;

	$help['Scheduling/UnfilledRequests.php'] = <<<HTML
<p>
	<i>Demandes non pourvues</i> est un rapport montrant les demandes non pourvues pour un groupe d'élèves.
</p>
<p>
	Vous devez d'abord sélectionner un (groupe d') élève(s) en utilisant l'écran de recherche "Trouver un Élève".
</p>
<p>
	Le rapport montre les informations de l'élève et les détails de la demande non pourvue (enseignant et tranche horaire demandés) ainsi que le nombre de classes qui ont été créées pour ce cours (via le programme Planification &gt; Cours). Vous pouvez aussi contrôler le nombre de places disponibles en cochant "Montrer les Places Disponibles" en haut de l'écran.
</p>
<p>
	Le fait de cliquer sur le nom de l'élève vous redirigera vers le programme Demandes Élève.
</p>
HTML;

	$help['Scheduling/IncompleteSchedules.php'] = <<<HTML
<p>
	<i>Emplois du Temps Incomplets</i> est un rapport montrant les élèves qui n'ont pas de classe planifiée pour une tranche horaire particulière.
</p>
<p>
	Vous devez d'abord sélectionner un (groupe d') élève(s) en utilisant l'écran de recherche "Trouver un Élève". Vous pouvez rechercher des élèves qui ont sont inscrits ou ont demandé un cours spécifique en cliquant sur le lien "Choisir" à côté des options de recherche "Cours" et "Demande" respectivement et en choisissant un cours depuis la fenêtre popup qui apparaît.
</p>
<p>
	Ensuite, les élèves de la liste ne sont pas planifiés pour les tranches horaires correspondant aux colonnes qui comportent une icône croix "X" rouge. Si la tranche horaire affiche une icône "cochée" verte, l'élève a une classe planifiée pour cette tranche horaire. Une icône croix "X" rouge indique donc une tranche horaire libre dans l'emploi du temps, qui peut-être remplie.
</p>
HTML;

	$help['Scheduling/AddDrop.php'] = <<<HTML
<p>
	<i>Rapport des Ajouts / Retraits</i> est un rapport montrant les classes qui ont été ajoutées ou retirées des emplois du temps des élèves durant la période sélectionnée. Vous pouvez sélectionner une période de temps différente grâce aux dates en haut de l'écran, et en cliquant sur le bouton "Go". Le rapport montre les les informations de l'élève ainsi que le Cours, la Classe, et les dates d'Inscription et de Retrait. Vous pouvez exporter le rapport vers une feuille de calcul en cliquant sur l'icône "Télécharger".
</p>
HTML;

	$help['Scheduling/Courses.php'] = <<<HTML
<p>
	<i>Cours</i> vous permet d'organiser les cours de votre école. Il existe trois divisions: les Matières, les Cours, et les Classes.
</p>
<p>
	Pour ajouter une division, cliquez sur l'icône Ajouter (+) dans la colonne correspondant au type que vous souhaitez ajouter. Ensuite, remplissez les informations requises dans les champs présents au-dessus de la liste et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour modifier une division, cliquez sur l'élément que vous souhaitez modifier, et cliquez sur la valeur que vous souhaitez changer dans la zone au-dessus des listes. Ensuite, éditez la valeur et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Finalement, pour supprimer un élément, sélectionnez-le en cliquant sur son titre dans la liste et cliquez sur le bouton "Effacer" en haut de l'écran. Vous devrez confirmer la suppression.
</p>
HTML;

	$help['Scheduling/Scheduler.php'] = <<<HTML
<p>
	<i>Lancer le Planificateur</i> inscrit les élèves de votre école selon les demandes qui leurs sont associées.
</p>
<p>
	Vous devez d'abord confirmer le lancement du planificateur. Là, vous pouvez aussi choisir de lancer le planificateur en "Mode Test" ce qui aura pour effet de ne pas enregistrer les emplois du temps des élèves.
</p>
<p>
	Une fois que le planificateur a fini, ce qui devrait prendre plusieurs minutes, il vous avertira des possibles conflits. Le résultat du Planificateur vous renseignera aussi si l'un des cours demandés ne possède plus de places disponibles. Si une demande n'a pas pu être satisfaite, vous pouvez changer les demandes en conséquence afin d'assurer une planification complète. Une fois les emplois du temps enregistrés, vous pourrez consulter le Rapport Emploi du Temps.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'teacher' ) :

	$help['Scheduling/Schedule.php'] = <<<HTML
<p>
	<i>Emploi du Temps</i> affiche l'emploi du temps d'un élève.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève".
</p>
HTML;

	// Parent & Élève.
else :

	$help['Scheduling/Schedule.php'] = <<<HTML
<p>
	<i>Emploi du Temps</i> affiche l'emploi du temps de l'élève.
</p>
HTML;

endif;


// GRADES ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Grades/ReportCards.php'] = <<<HTML
<p>
	<i>Bulletins de Notes</i> est un outil qui vous permet d'imprimer les bulletins de notes d'un ou plusieurs élèves.
</p>
<p>
	Vous pouvez rechercher les élèves inscrits à un cours spécifique en cliquant sur le lien "Choisir" à côté de l'option de recherche "Cours" et en choisissant un cours depuis la fenêtre popup qui apparaît. Vous pouvez aussi limiter votre recherche en définissant la Moyenne (non-)pondérée, le classement ou les notes obtenues désirés au moyen de la recherche avancée. Par exemple, cela vous permet de trouver les élèves dans le top 10 de leur classe, ceux qui sont en échec, ou tous les élèves qui on échoué pour au moins un cours durant la période scolaire sélectionnée.
</p>
<p>
	Aussi, vous pouvez choisir d'imprimer les bulletins de notes avec des étiquettes d'adresse. Les bulletins de notes auront leur étiquette d'adresse positionnée de manière a être visible dans une enveloppe à fenêtre lorsque la feuille est pliée en trois. Plus d'un bulletin de notes peut-être imprimé par élève si l'élève a des tuteurs résidant à différentes adresses.
</p>
<p>
	Avant d'imprimer les bulletins de notes, vous devez sélectionner quelles périodes scolaires afficher sur le bulletin de notes en cochant les périodes scolaires désirées.
</p>
<p>
	Les bulletins de notes seront automatiquement téléchargés au format PDF pour l'impression lorsque vous cliquez sur le bouton "Créer Bulletins de Notes pour les Élèves Sélectionnés".
</p>
HTML;

	$help['Grades/HonorRoll.php'] = <<<HTML
<p>
	<i>Mentions</i> vous permet de créer des listes d'élèves avec mention ou bien des certificats.
</p>
<p>
	Les valeurs de Moyenne pour la Mention sont configurées dans le programme Notes &gt; Échelles de Notation.
</p>
<p>
	Vous devez d'abord sélectionner un (groupe d') élève(s) en utilisant l'écran de recherche "Trouver un Élève". Vous pouvez rechercher des élèves qui sont qualifiés pour la "Mention" ou la "Mention avec Distinction" en cochant les cases à cocher respectives. Vous pouvez aussi recherchez des élèves qui ont demandé un cours spécifique en cliquant sur le lien "Choisir" à côté de l'option de recherche "Cours" et en choisissant un cours de puis la fenêtre popup qui apparaît.
</p>
<p>
	Ensuite, vous pouvez générer des "Certificats" ou la "Liste" des élèves qualifiés en sélectionnant l'option correspondante en haut de l'écran. Le texte du Certificat peut-être personnalisé. Finalement, cliquez sur le bouton "Créer les Mentions pour les Élèves Sélectionnés" générer les certificats de Mention ou la Liste des élèves qualifiés au format PDF pour l'impression ou l'envoi par email. Alternativement, vous pouvez cliquer sur l'icône "Télécharger" afin de générer une feuille de calcul avec ces données.
</p>
HTML;

	$help['Grades/CalcGPA.php'] = <<<HTML
<p>
	<i>Calculer la Moyenne</i> calcule et enregistre la moyenne et le classement de chaque élève de l'école en se basant sur leurs notes.
</p>
<p>
	Vous devez confirmer le calcul de la Moyenne. Ici, vous pouvez aussi spcécifier pour quel période scolaire la moyenne est calculée. La moyenne est calculée sur la "Base de l'échelle de notation" spécifiée dans paramétrage de l'école.
</p>
<p>
	Le programme Calculer la Moyenne calcule la moyenne pondérée par cours en multipliant la valeur moyenne de la note du cours que l'élève a reçu par le nombre de crédits du cours. Ensuite, il divise cette valeur par le nombre de base de l'échelle de notation. Pour la moyenne non pondérée, le programme Calculer la Moyenne prend simplement la valeur moyenne de la note du cours que l'élève a reçu. Après avoir calculé les points moyens obtenus pour chaque cours, le programme calcule la moyenne de ces valeurs pour déterminer la moyenne actualisée de l'élève. Il trie ensuite ces valeurs pour déterminer le classement. Si plus d'un élève a la même moyenne, ils partageront la même position au classement.
</p>
HTML;

	$help['Grades/Transcripts.php'] = <<<HTML
<p>
	<i>Livrets Scolaires</i> est un outil qui vous permet d'imprimer les livrets scolaires pour un ou plusieurs élèves.
</p>
<p>
	Vous pouvez rechercher les élèves inscrits à un cours spécifique en cliquant sur le lien "Choisir" à côté de l'option de recherche "Cours" et en choisissant un cours depuis la fenêtre popup qui apparaît. Vous pouvez aussi limiter votre recherche en définissant la Moyenne (non-)pondérée, le classement ou les notes obtenues désirés au moyen de la recherche avancée. Par exemple, cela vous permet de trouver les élèves dans le top 10 de leur classe, ceux qui sont en échec, ou tous les élèves qui on échoué pour au moins un cours durant la période scolaire sélectionnée.
</p>
<p>
	Avant d'imprimer les livrets scolaires, vous devez sélectionner quelles périodes scolaires afficher sur le livret scolaire en cochant les cases à cocher des périodes scolaires désirées.
</p>
<p>
	Les livrets scolaires seront automatiquement téléchargés au format PDF pour l'impression lorsque vous cliquez sur le bouton "Valider".
</p>
HTML;

	$help['Grades/TeacherCompletion.php'] = <<<HTML
<p>
	<i>État d'Avancement</i> est un rapport qui affiche les enseignants n'ayant pas saisi les notes pour une période scolaire donnée.
</p>
<p>
	Les croix rouges indiquent que l'enseignant n'a pas saisi les notes de la période scolaire courante pour cette classe.
</p>
<p>
	Vous pouvez sélectionner le trimestre courant, ou le semestre depuis le menu déroulant en haut de l'écran. Pour changer de trimestre, changez de période scolaire grâce au menu déroulant situé dans le menu latéral. Vous pouvez aussi afficher seulement une tranche horaire en la choisissant grâce au menu déroulant des tranches horaires en haut de l'écran.
</p>
HTML;

	$help['Grades/GradeBreakdown.php'] = <<<HTML
<p>
	<i>Répartition des Notes</i> est un rapport qui permet de consulter le nombre de fois que chaque note a été donnée par un enseignant.
</p>
<p>
	Vous pouvez sélectionner le trimestre, ou le semestre courant depuis le menu déroulant en haut de l'écran. Pour changer de trimestre, changez de période scolaire grâce au menu déroulant situé dans le menu latéral.
</p>
HTML;

	$help['Grades/StudentGrades.php'] = <<<HTML
<p>
	<i>Notes des Élèves</i> vous permet de voir les notes obtenues par un élève.
</p>
<p>
	Vous pouvez rechercher les élèves inscrits à un cours spécifique en cliquant sur le lien "Choisir" à côté de l'option de recherche "Cours" et en choisissant un cours depuis la fenêtre popup qui apparaît. Vous pouvez aussi limiter votre recherche en définissant la Moyenne (non-)pondérée, le classement ou les notes obtenues désirés au moyen de la recherche avancée. Par exemple, cela vous permet de trouver les élèves dans le top 10 de leur classe, ceux qui sont en échec, ou tous les élèves qui on échoué pour au moins un cours durant la période scolaire sélectionnée.
</p>
HTML;

	$help['Grades/FinalGrades.php'] = <<<HTML
<p>
	<i>Notes Définitives</i> vous permet de consulter les notes définitives que les élèves ont obtenues.
</p>
<p>
	Vous devez d'abord sélectionner un (groupe d') élève(s) en utilisant l'écran de recherche "Trouver un Élève".
</p>
<p>
	Ensuite, sélectionnez ce que vous désirez inclure sur la Liste de Notes: "Enseignant", "Commentaires" et les "Absences Journalières" sont pré-cochés par défaut. Si vous désirez inclure d'autres informations, veuillez les cocher. N'oubliez pas de cocher les Périodes Scolaires à inclure sur la Liste de Notes.
</p>
<p>
	Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Vous pouvez sélectionner tous les élèves de la liste en cochant la case à cocher de l'en-tête de liste.
</p>
<p>
	Finalement, cliquez sur le bouton "Créer les Listes de Notes pour les Élèves Sélectionnés".
</p>
<p>
	Veuillez noter que si vous sélectionnez seulement UNE période scolaire, il est possible de supprimer une Note Définitive en cliquant sur l'icône Effacer (-) sur le côté gauche, et en confirmant votre choix.
</p>
HTML;

	$help['Grades/GPARankList.php'] = <<<HTML
<p>
	<i>Liste Moyenne / Classement</i> est un rapport qui permet de consulter les moyennes pondérées et non pondérées, ainsi que le classement de chaque élève de l'école.
</p>
<p>
	Comme pour chaque liste dans RosarioSIS, vous pouvez trier par valeur en cliquant sur les en-têtes de liste correspondants. Par exemple, vous pouvez trier par note en cliquant sur l'en-tête de colonne "Note". De façon similaire, vous pouvez trier par moyenne non pondérée en cliquant sur l'en-tête de colonne "Moyenne Non-Pondérée".
</p>
HTML;

	$help['Grades/ReportCardGrades.php'] = <<<HTML
<p>
	<i>Échelles de Notation</i> vous permet de configurer les notes du bulletin de notes de l'école. Les notes du bulletin de notes sont utilisées dans le programme Saisie des Notes par les enseignants et dans la plupart des rapports de Notes. Les Notes du bulletin de notes incluent les notes de lettre ainsi que les commentaires de note qu'un enseignant peut choisir au moment de saisir les notes.
</p>
<p>
	Pour ajouter un note du bulletin de notes, remplissez les champs titre, valeur moyenne, et ordre de tri dans les champs vides en bas de la liste de notes et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour ajouter un commentaire, entrez le titre du nouveau commentaire dans le champ vide en bas de la liste des commentaires.
</p>
<p>
	Pour modifier une note, cliquez sur une de ses informations, éditez la valeur, et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour supprimer une note, cliquez sur l'icône effacer (-) à côté de la note que vous souhaitez supprimer. Vous devrez confirmer la suppression.
</p>
<p>
	Pour ajouter ou configurer une échelle de notation, cliquez d'abord sur l'onglet avec l'icône plus (+). Pour chaque échelle de notation, vous devrez ajuster la valeur de l'échelle, la moyenne (note minimum pour obtenir les crédits), ainsi que divers minimum de moyenne pour les mentions.
</p>
HTML;

	$help['Grades/ReportCardComments.php'] = <<<HTML
<p>
	<i>Commentaires du Bulletin de Notes</i> vous permet de configurer les commentaires du bulletin de notes de l'école, pour chaque cours ou pour tous les cours.
</p>
<p>
	L'onglet "Tous les Cours" est là où vous créerez les Commentaires qui s'appliquent à tous les Cours, par exemple pour le comportement, ou une caractéristique des élèves que tous les cours ont en commun. L'onglet plus (+) est là où vous ajouterez d'autres commentaires, notamment les onglets et commentaires spécifiques à un cours.
</p>
<p>
	L'onglet "Général" contient les commentaires qui sont ajoutés au moment de la saisie des notes des élèves dans le programme "Saisie des Notes". Les enseignants pourront utiliser le menu déroulant de l'onglet "Général" pour ajouter un ou plusieurs commentaires prédéfinis au bulletin de notes. Veuillez noter que RosarioSIS propose des symboles de substitution qui peuvent être utilisés dans ces commentaires: "^n" sera remplacé par le prénom de l'élève, tandis que "^s" sera remplacé par un pronom de genre approprié. Par exemple, le commentaire "^n ne prépare pas ^s devoirs" sera traduit par "John ne prépare pas ses devoirs" dans le bulletin de notes de John Smith.
</p>
<p>
	L'onglet "Tous les Cours" vous permet de créer des Commentaires qui s'appliquent à tous les Cours. Entrez le nom du Commentaire et associez-le à une "Échelle de Code" (créée dans le programme "Codes de Commentaire") grâce au menu déroulant. Le résultat sera une nouvelle colonne pour le commentaire dans le programme "Saisie des Notes", sous l'onglet "Tous les Cours". Cette colonne affichera un menu déroulant avec les codes de commentaire de l'échelle associée.
</p>
<p>
	Pour créer des commentaires spécifiques à un cours, sélectionnez tout d'abord un cours grâce aux menus déroulants en haut de la page. Ensuite, cliquez sur l'onglet avec l'icône plus (+) afin de créer une catégorie de commentaires. Cliquez sur "Enregistrer", alors, un nouvel onglet portant le nom de la catégorie apparaitra. Là, il est possible d'ajouter des commentaires individuels, un par un, et de les associer à une "Échelle de Code" (créée dans le programme "Codes de Commentaire") grâce au menu déroulant. Le résultat sera un nouvel onglet dans le programme "Saisie des Notes". L'onglet sera nommé comme la catégorie de commentaire et montrera une colonne pour chaque commentaire de cette catégorie. Les colonnes afficheront un menu déroulant contenant les codes de commentaire des échelles associées.
</p>
HTML;

	$help['Grades/ReportCardCommentCodes.php'] = <<<HTML
<p>
	<i>Codes de Commentaire</i> vous permet de créer des échelles de commentaire qui génereront des menus déroulants de codes de commentaire dans le programme Saisie des Notes. Ensuite, ces codes seront affichés avec leur commentaire associé dans le Bulletin de Notes.
</p>
<p>
	Pour créer une Échelle de Commentaire, cliquez sur l'onglet avec l'icône plus (+). Donnez un nom à votre échelle de commentaire, et ajoutez un commentaire optionnel, ensuite cliquez sur "Enregistrer". UN nouvel onglet portant le nom de votre nouvelle Échelle de Commentaire apparaitra. Cliquez sur l'onglet de l'échelle de commentaire pour la sélectionner. Il est alors possible d'ajouter, un par un, les codes de commentaire de l'échelle en remplissant leur "Titre" (entrez le code ici), "Nom Abrégé" et "Commentaire" (légende du code qui apparaitra sur le bulletin de notes).
</p>
HTML;

	$help['Grades/EditHistoryMarkingPeriods.php'] = <<<HTML
<p>
	<i>Périodes Scolaires Antérieures</i> vous permet de créer périodes scolaires pour les notes des années scolaires antérieures.
</p>
<p>
	Utilisez d'abord ce programme si vous souhaitez saisir les notes des années scolaires antérieures dans RosarioSIS, celles qui ont été attribuées avant l'installation de RosarioSIS, ou si vous souhaitez saisir les notes d'un élève transféré dans votre école. Une fois les périodes scolaires antérieures ajoutées, il est possible de les sélectionner au sein du programme Éditer les Notes des Élèves.
</p>
<p>
	Veuillez noter que le champ "Date de Saisie des Notes" détermine l'ordre des Périodes Scolaires Antérieures au moment de saisir les notes ou pour la génération du Livret Scolaire et doit donc être entrée de manière appropriée. Aussi, chaque période scolaire antérieure doit être unique.
</p>
HTML;

	$help['Grades/EditReportCardGrades.php'] = <<<HTML
<p>
	<i>Éditer les Notes des Élèves</i> vous permet de saisir les notes des années scolaires antérieures d'un élève ou les notes d'un élève transféré dans votre école.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève".
</p>
<p>
Maintenant, pour l'élève sélectionné, ajoutez la période scolaire (typiquement, une période scolaire antérieure que vous avez créee via le programme Périodes Scolaires Antérieures) en la sélectionnant grâce au menu déroulant "Nouvelle Période Scolaire". Ensuite, entrez le niveau scolaire de l'élève sélectionné et cliquez sur "Enregistrer".
</p>
<p>
	Vous pouvez ajouter les notes de l'élève dans l'onglet "Notes". Saisissez le "Nom du Cours" et les notes associées, ensuite cliquez sur "Enregistrer". Veuillez noter que vous pouvez utiliser une échelle de notation personnalisée pour le calcul de la moyenee.
</p>
<p>
	RosarioSIS utilise les crédits pour calculer la moyenne. Veuillez passer à l'onglet "Crédits" et ajuster les crédits pour chaque cours.
</p>
HTML;

	$help['Grades/MassCreateAssignments.php'] = <<<HTML
<p>
	<i>Créer des Devoirs en Masse</i> vous permet de créer des devoirs pour plusieurs classes à la fois. Il existe les types de devoir et les devoirs en eux-même.
</p>
<p>
	Vous aurez probablement des types de devoir nommés "Devoirs maison", "Contrôles", ou bien "Quiz". Les types de devoir sont définis pour toutes les classes d'un même cours.
</p>
<p>
	Pour ajouter un type de devoir, cliquez sur l'icône Ajouter (+) dans la colonne type de devoir. Ensuite, renseignez les informations dans les champs au dessus de la liste de types de devoir. Sélectionnez les Cours désirés dans la liste en bas de l'écran et cliquez sur le bouton "Créer le Type de Devoir pour les Cours Sélectionnés".
</p>
<p>
	Si vous définissez le "Pourcentage de la Note Définitive", les enseignants le verront seulement si ils ont coché la case "Pondérer les Notes" dans la Configuration de leur Carnet de Notes.
</p>
<p>
	Pour ajouter un devoir, cliquez sur le type de devoir désiré dans la colonne type de devoir. Ensuite, renseignez les informations dans les champs au dessus de la liste de types de devoir. Sélectionnez les Classes désirées dans la liste en bas de l'écran cliquez sur le bouton "Créer le Devoir pour les Classes Sélectionnées".
</p>
<p>
	Si vous saisissez 0 "Points", cela vous permet de donner des Points Bonus aux Élèves.
</p>
<p>
	Si vous cochez "Activer la Remise de Devoir", les Élèves (ou leurs Parents) peuvent rendre le devoir (uploader un fichier et/ou laisser un message). La remise est possible depuis la date de début et jusqu'à la date d'échéance. Si aucune date d'échéance n'a été définie, la remise peut se faire jusqu'à la fin du trimestre. Les enseignants pourront ensuite consulter les devoirs grâce au programme "Notes".
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'teacher' ) :

	$help['Grades/InputFinalGrades.php'] = <<<HTML
<p>
	<i>Saisie des Notes</i> vous permet de saisir les notes du trimestre, semestre ou de la période intermédiaire pour tous vos élèves de la classe courante. Par défaut, ce programme liste les élèves de la classe courante pour le trimestre courant. Vous pouvez changer le trimestre en changeant la période scolaire grâce au menu déroulant du menu latéral. Aussi, vous pouvez sélectionner le semestre ou la période intermédiaire courant(e) en changeant la période scolaire grâce au menu déroulant situé en haut de l'écran.
</p>
<p>
	Une fois la période scolaire correcte sélectionnée, vous pouvez saisir les notes des élève en sélectionnant la note obtenue par chaque élève et en entrant les commentaires souhaités. Une fois les notes et commentaires saisis, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Si vous utilisez le Carnet de Notes, RosarioSIS peut calculer automatiquement la note trimestrielle de chaque élève en cliquant sur lien "Utiliser les Notes du Carnet" au-dessus de la liste. En cliquant sur le lien, les notes de chaque élève sont enregistrées et la liste rafraîchie.
</p>
<p>
	Si la période scolaire dans laquelle vous êtes est une Période Intermédiaire, lorsque vous cliquez sur le lien "Utiliser les Notes du Carnet", les notes prises en compte seront limitées aux Devoirs dont la Date de Rendu est comprise dans la Période intermédiaire, ou ceux qui n'ont pas de Date de Rendu.
</p>
HTML;

	$help['Grades/Configuration.php'] = <<<HTML
<p>
	<i>Configuration</i> vous permet de configurer le carnet de notes.
</p>
<p>
	Vous pouvez configurer le carnet de notes afin d'arrondir les notes au nombre supérieur, inférieur ou bien garder le comportement normal. Le comportement normal arrondi 19.5 à 20 et 19.4 à 19.
</p>
<p>
	Vous pouvez aussi configurer les points limites pour chaque note (lettrée). Par exemple, si vous définissez les points limites pour les notes A+, A, et A- à 99, 91, et 90 respectivement, un élève ayant de 99% à 100% aura un A+, un autre élève ayant de 91% à 98% aura un A, et un élève ayant 90% aura un A-. Le point limite pour la note F devrait probablement être 0.
</p>
<p>
	Finalement, vous pouvez aussi configurer les pourcentages des notes définitives pour chaque semestre. Ces valeurs seront utilisées au moment de calculer la moyenne des notes des trimestres lors du calcul de la note du semestre.
</p>
HTML;

	$help['Grades/Assignments.php'] = <<<HTML
<p>
	<i>Devoirs</i> vous permet de configurer les devoirs. Il existe les types de devoir et les devoirs en eux-même.
</p>
<p>
	Vous aurez probablement des types de devoir nommés "Devoirs maison", "Contrôles", ou bien "Quiz". Les types de devoir sont définis pour toutes les classes d'un même cours. Donc, si vous enseignez les Mathématiques dans 2 classes, vous devrez ajouter les types de devoir seulement une fois pour ces 2 classes.
</p>
<p>
	Pour ajouter un type de devoir ou un devoir, cliquez sur l'icône Ajouter (+) dans la colonne correspondante. Ensuite, renseignez les informations dans les champs au dessus des listes de devoirs / types et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Si vous saisissez 0 "Points", cela vous permet de donner des Points Bonus aux Élèves.
</p>
<p>
	Si vous cochez "Appliquer à toutes les Classes de ce Cours", le devoir sera ajouté pour chaque classe du cours, de manière similaire à l'ajout des types de devoir.
</p>
<p>
	Si vous cochez "Activer la Remise de Devoir", les Élèves (ou leurs Parents) peuvent rendre le devoir (uploader un fichier et/ou laisser un message). La remise est possible depuis la date de début et jusqu'à la date d'échéance. Si aucune date d'échéance n'a été définie, la remise peut se faire jusqu'à la fin du trimestre. Vous pourrez ensuite consulter les devoirs grâce au programme "Notes".
</p>
<p>
	Pour modifier un devoir ou un type, cliquez sur le devoir ou type désiré et cliquez sur la valeur que vous souhaitez changer dans l'encadre au-dessus des listes de devoirs / types. Ensuite, éditez la valeur et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Finalement, pour supprimer un élément, sélectionnez-le en cliquant sur son titre dans la liste et cliquez sur le bouton "Effacer" en haut de l'écran. Vous devrez confirmer la suppression.
</p>
HTML;

	$help['Grades/Grades.php'] = <<<HTML
<p>
	<i>Notes</i> vous permet de saisir les notes des devoirs pour tous vos élèves de la classe courante. Par défaut, ce programme liste les élèves de votre première classe. Vous pouvez changer la classe courante au moyen du menu déroulant situé dans le menu latéral.
</p>
<p>
	Une fois la classe correcte sélectionnée, vous verrez le total des points et la note cumulée de chaque élève de votre classe. Vous pouvez voir les notes d'un devoir particulier en sélectionnant le devoir depuis le menu déroulant en haut de l'écran. Alors, vous pourrez saisir une nouvelle note en entrant les points acquis dans le champ vide à côté du nom de l'élève; vous pourrez aussi modifier une note éxistante en cliquant sur les points acquis et en changeant la valeur. Une fois les notes modifiées, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Vous pouvez aussi consulter et modifier toutes les notes d'un même élève en cliquant sur le nom de l'élève dans la liste. Procédez de manière identique pour la saisie les notes.
</p>
HTML;

	$help['Grades/ProgressReports.php'] = <<<HTML
<p>
	<i>Bulletins Intermédiaires</i> est un outil qui vous permet d'imprimer des bulletins intermédiaires pour un ou plusieurs élèves.
</p>
<p>
	Vous pouvez imprimer les bulletins intermédiaires avec des étiquettes d'adresse. Les bulletins intermédiaires auront leur étiquette d'adresse positionnée de manière a être visible dans une enveloppe à fenêtre lorsque la feuille est pliée en trois. Plus d'un bulletin intermédiaire peut-être imprimé si l'élève a des tuteurs résidant à différentes adresses.
</p>
<p>
	Les bulletins intermédiaires seront automatiquement téléchargés au format PDF pour l'impression lorsque vous cliquez sur le bouton "Valider".
</p>
HTML;

	$help['Grades/AnomalousGrades.php'] = <<<HTML
<p>
	<i>Notes non Conformes</i> est un rapport qui vous aidera à repérer les notes manquantes, non conformes ou les dispenses. Les notes apparaissant dans ce rapport ne sont PAS problématiques, mais vous POURREZ vouloir les réviser. Les notes manquantes, négatives et les dispenses, ou les notes qui sont des Points Bonus ou celles qui excèdent les 100% sont affichées. La colonne "Problème" indique la raison pour laquelle la note est non conforme.
</p>
<p>
	Vous pouvez sélectionner la classe en utilisant le menu déroulant du menu latéral. Vous pouvez aussi sélectionnez quel type de notes "non conformes" que vous souhaitez que le rapport affiche.
</p>
HTML;

	// Parent & Élève.
else :

	$help['Grades/ReportCards.php'] = <<<HTML
<p>
	<i>Bulletins de Notes</i> est un outil qui vous permet d'imprimer les bulletins de notes de l'élève.
</p>
<p>
	Avant d'imprimer le bulletin de notes, vous devez sélectionner quelles périodes scolaires afficher sur le bulletin de notes en cochant les cases à cocher des périodes scolaires désirées.
</p>
<p>
	Le bulletin de notes sera automatiquement téléchargé au format PDF pour l'impression lorsque vous cliquez sur le bouton "Valider".
</p>
HTML;

	$help['Grades/Transcripts.php'] = <<<HTML
<p>
	<i>Livrets Scolaires</i> est un outil qui vous permet d'imprimer les livrets scolaires de l'élève.
</p>
<p>
	Avant d'imprimer le livret scolaire, vous devez sélectionner quelles périodes scolaires afficher sur le livret scolaire en cochant les cases à cocher des périodes scolaires désirées.
</p>
<p>
	Le livret scolaire sera automatiquement téléchargé au format PDF pour l'impression lorsque vous cliquez sur le bouton "Valider".
</p>
HTML;

	$help['Grades/StudentAssignments.php'] = <<<HTML
<p>
	<i>Devoirs</i> vous permet de consulter les devoirs de l'élève.
</p>
<p>
	Sur la page d'un devoir particulier, il vous est possible de rendre le devoir si cela est permis par l'enseignant. Pour ce faire, vous pourrez uploader un fichier et/ou laisser un message.
</p>
<p>
	La remise d'un devoir peut se faire jusqu'à la date d'échéance. Si aucune date d'échéance n'a été définie, la remise peut se faire jusqu'à la fin du trimestre.
</p>
<p>
	Vous pouvez changer de période scolaire grâce au menu déroulant situé dans le menu latéral.
</p>
HTML;

	$help['Grades/StudentGrades.php'] = <<<HTML
<p>
	<i>Carnet de Notes</i> vous permet de consulter les notes de l'élève.
</p>
<p>
	Vous pouvez changer de période scolaire grâce au menu déroulant situé dans le menu latéral.
</p>
HTML;

	$help['Grades/GPARankList.php'] = <<<HTML
<p>
	<i>Liste Moyenne / Classement</i> est un rapport qui affiche la moyenne non pondérée, pondérée, ainsi que le classement de l'élève.
</p>
HTML;

endif;


// ATTENDANCE ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Attendance/Administration.php'] = <<<HTML
<p>
	<i>Administration</i> vous permet de consulter et modifier les présences d'un élève et ce pour n'importe quel jour.
</p>
<p>
	Afin de modifier le statut de présence d'un élève pour une tranche horaire, cliquez sur la valeur courante et sélectionnez le nom abrégé du code de présence que vous souhaitez assigner à l'élève. Ue fois toutes les modifications désirées effectuées, cliquez sur le bouton "Mettre à Jour" en haut de l'écran. Vous pouvez aussi limiter les liste des élèves par code de présence pour le jour courant. Par example et par défaut, tous les élèves ayant un code de présence appartenant au statut "Absent" sont listés. Cela grâce au menu déroulant en haut à gauche de l'écran qui contient le texte "Abs." Ce menu permet de changer de code de présence, et seulement les élèves qui se sont vu assigner ce code durant le jour courant seront alors affichés. Ce même menu propose aussi l'option "Tou(te)s" qui affichera tous les élèves pour lesquels les absences ont été saisies. Vous pouvez ajouter un code de présence en cliquant sur l'icône ajouter (+) à côté du menu déroulant des codes de présence. Si vous sélectionnez un second code de présence, le programme listera les élèves qui se sont vu assigner les deux codes dans la journée.
</p>
<p>
	Vous pouvez modifier la date courante grâce aux menu déroulants pour la date en haut à gauche de l'écran.
</p>
<p>
	Après avoir modifié les codes de présence ou la date courante, cliquez sur le bouton "Mettre à Jour" afin de rafraîchir l'écran avec les nouveaux paramètres.
</p>
<p>
	Vous pouvez aussi consulter les codes de présence attribués à l'élève par les enseignants ainsi que consulter et saisir un commentaire pour chaque classe en cliquant sur le nom de l'élève.
</p>
<p>
	Le fait de cliquer sur "Élève Sélectionné" en haut de l'écran montrera les présences de l'élève courant du menu latéral.
</p>
HTML;

	$help['Attendance/AddAbsences.php'] = <<<HTML
<p>
	<i>Ajouter Absences</i> vous permet d'ajouter des absences à un groupe d'élèves en une fois.
</p>
<p>
	Premièrement, recherchez des élèves. Notez que vous pouvez rechercher les élèves inscrits à un cours spécifique ou à une certaine activité. Ce peut-être utile par exemple pour ajouter une absence aux élèves de chaque classe de M. Smith ou bien à l'équipe de football qui sera en voyage ce jour pour un match.
</p>
<p>
	Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Vous pouvez sélectionner tous les élèves de la liste en cochant la case à cocher de l'en-tête de liste. Vous pouvez aussi spécifier pour quelles tranches horaire marquer les élèves sélectionnés, le code d'absence, la raison de l'absence, et la date dans l'encadre au dessus de la liste d'élèves. Une fois les élèves, les tranches horaires, le code d'absence, la raison, et la date désirés sélectionnés, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
HTML;

	$help['Attendance/Percent.php'] = <<<HTML
<p>
	<i>Présence Journalière Moyenne</i> est un rapport qui permet de consulter le nombre d'élèves, les jours possibles, le nombre de jours de présence élève, le nombre de jours d'absence élève, la Présence Journalière Moyenne (PJM), le nombre moyen d'élèves présents en classe par jour, et le nombre moyen d'élèves absents par jour pour une période de temps donné. Ces nombres sont répartis par niveau scolaire.
</p>
<p>
	Vous pouvez changer l'intervalle de temps affiché grâce aux menus déroulants des dates en haut de l'écran et en cliquant sur le bouton "Go". Vous pouvez aussi limiter les résultats en cherchant par genre ou n'importe quel autre champ élève en cliquant sur le lien "Avancé".
</p>
HTML;

	$help['Attendance/Percent.php&list_by_day=true'] = <<<HTML
<p>
	<i>Présence Moyenne par Jour</i> est un rapport qui permet de consulter le nombre d'élèves, les jours possibles, le nombre de jours de présence élève, le nombre de jours d'absence élève, la Présence Journalière Moyenne (PJM) de la journée, ce pour une période de temps donné. Ces nombres sont répartis par niveau scolaire.
</p>
<p>
	Vous pouvez changer l'intervalle de temps affiché grâce aux menus déroulants des dates en haut de l'écran et en cliquant sur le bouton "Go". Vous pouvez aussi limiter les résultats en cherchant par genre ou n'importe quel autre champ élève en cliquant sur le lien "Avancé".
</p>
HTML;

	$help['Attendance/DailySummary.php'] = <<<HTML
<p>
	<i>Tableau des Absences</i> est un rapport qui permet de consulter les statuts de présence des élèves pour chaque jour d'une période de temps donnée.
</p>
<p>
	Après avoir recherché les élèves, vous pouvez modifier l'intervalle de temps en changeant les dates grâce aux menus déroulants en haut de l'écran et en cliquant sur le bouton "Go". La liste affiche la valeur de présence journalière de l'élève pour chaque jour avec des code de couleur. Le rouge signifie que l'élève a été absent toute la journée, le jaune qu'il a été absent une demi-journée et le vert qu'il a été présent toute la journée.
</p>
<p>
	Vous pouvez consulter les données de présence d'un élève en particulier en cliquant sur son nom dans la liste.
</p>
HTML;

	$help['Attendance/StudentSummary.php'] = <<<HTML
<p>
	<i>Résumé des Absences</i> est un rapport qui permet de consulter les jours pour lesquels un élève a été absent.
</p>
<p>
	Après avoir sélectionné un élève, vous pouvez modifier l'intervalle de temps en changeant les dates grâce aux menus déroulants en haut de l'écran et en cliquant sur le bouton "Go". LA liste montre les absences de l'élève pour chaque tranche horaire de chaque jour ou il a été absent. Une croix "x" rouge indique que l'élève a été absent pour la période correspondante.
</p>
HTML;

	$help['Attendance/TeacherCompletion.php'] = <<<HTML
<p>
	<i>État d'Avancement</i> est un rapport qui permet de consulter quels enseignants n'ont pas encore saisi les absences pour un jour donné.
</p>
<p>
	Les croix rouges indiquent que l'enseignant n'a pas saisi les absences pour le jour et la tranche horaire donnée.
</p>
<p>
	Vous pouvez sélectionner la date depuis le menu déroulant en haut de l'écran. Vous pouvez aussi ne montrer qu'une tranche horaire en la sélectionnant depuis le menu déroulant en haut de l'écran. Après avoir choisi une date ou une tranche horaire, la liste sera automatiquement rafraîchie avec les nouveaux paramètres.
</p>
HTML;

	$help['Attendance/FixDailyAttendance.php'] = <<<HTML
<p>
	<i>Recalculer les Absences Journalières</i> est un outil qui sert à recalculer les absences journalières pour une période de temps donnée.
</p>
<p>
	Sélectionnez l'intervalle de temps et cliquez sur "OK". Toutes les données de présence seront alors calculées pour la journée complète et la demi-journée. Au cas ou le système ne réponds plus, veuillez recommencer avec un intervalle de temps plus court. Grâce à cet utilitaire, vous pourrez éviter les problêmes liés aux absences de cours non renseignées.
</p>
HTML;

	$help['Attendance/DuplicateAttendance.php'] = <<<HTML
<p>
	<i>Effacer les Absences Redondantes</i> est un outil permettant de repérer et supprimer les absences qui ont été saisies pour un élève APRÈS qu'il ait été retiré de l'école / d'un cours.
</p>
<p>
	À utiliser au cas où un élève est retiré d'un cours de manière rétro-active, mais que les absences ont déjà été saisies par les enseignants ou administrateurs.
</p>
HTML;

	$help['Attendance/AttendanceCodes.php'] = <<<HTML
<p>
	<i>Codes de Présence</i> vous permet de configurer les codes de présence de votre école. Les codes de présence sont utilisés par les enseignants dans le programme "Saisir les Absences" (ainsi que dans la majorité des rapports de Présence) et spécifient si l'élève a été présent durant la classe, et, si il a été absent, la raison.
</p>
<p>
	Pour ajouter un code de présence, remplissez les champs titre, nom abrégé, type, et code d'état. Indiquez si le code devrait être le code coché par défaut pour les enseignants dans les champs libres en bas de la liste et cliquez sur le bouton "Enregistrer". De manière générale, le code de présence nommé "Présent" sera le code par défaut. Si le code de présence est du type "Enseignant," les enseignants pourront alors le sélectionner dans le programme "Saisir les Absences". Les administrateurs peuvent quant à eux assigner tous les codes aux élèves.
</p>
<p>
	Pour modifier un code de présence, cliquez sur une de ses informations, éditez la valeur, et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour supprimer un code de présence, cliquez sur l'icône effacer (-) à côté du code de présence que vous souhaitez supprimer. Vous devrez confirmer la suppression.
</p>
HTML;

	$help['Custom/AttendanceSummary.php'] = <<<HTML
<p>
	<i>Rapport des Absences</i> est un rapport qui résume les états de présence d'un élève pour chaque jour de l'année scolaire, et ce sur une feuille.
</p>
<p>
	Vous devez d'abord sélectionner un (groupe d') élève(s) en utilisant l'écran de recherche "Trouver un Élève". Vous pouvez rechercher des élèves qui ont demandé un cours spécifique en cliquant sur le lien "Choisir" à côté de l'option de recherche "Cours" et en choisissant un cours de puis la fenêtre popup qui apparaît.
</p>
<p>
	Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Vous pouvez sélectionner tous les élèves de la liste en cochant la case à cocher de l'en-tête de liste. Une fois les élèves désirés sélectionnés, cliquez sur "Créer le Rapport des Absences pour les Élèves Sélectionnés" pour générer le rapport au format PDF.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'teacher' ) :

	$help['Attendance/TakeAttendance.php'] = <<<HTML
<p>
	<i>Saisir les Absences</i> vous permet de saisir les absences pour tous les élèves de la classe courante. Par défaut, ce programme liste les élèves de votre première classe. Vous pouvez changer la classe courante au moyen du menu déroulant situé dans le menu latéral.
</p>
<p>
	Une fois la classe correcte sélectionnée, vous pourrez saisir les absences en sélectionnant le code de présence correspondant à chaque élève. Une fois que vous aurez saisi les absences pour tous vos élèves, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
HTML;

	$help['Attendance/DailySummary.php'] = <<<HTML
<p>
	<i>Tableau des Absences</i> est un rapport qui permet de consulter les statuts de présence des élèves pour chaque jour d'une période de temps donnée.
</p>
<p>
	Après avoir recherché les élèves, vous pouvez modifier l'intervalle de temps en changeant les dates grâce aux menus déroulants en haut de l'écran et en cliquant sur le bouton "Go". La liste affiche la valeur de présence journalière de l'élève pour chaque jour avec des code de couleur. Le rouge signifie que l'élève a été absent toute la journée, le jaune qu'il a été absent une demi-journée et le vert qu'il a été présent toute la journée.
</p>
<p>
	Vous pouvez consulter les données de présence d'un élève en particulier en cliquant sur son nom dans la liste.
</p>
HTML;

	$help['Attendance/StudentSummary.php'] = <<<HTML
<p>
	<i>Résumé des Absences</i> est un rapport qui permet de consulter les jours pour lesquels un élève a été absent.
</p>
<p>
	Après avoir sélectionné un élève, vous pouvez modifier l'intervalle de temps en changeant les dates grâce aux menus déroulants en haut de l'écran et en cliquant sur le bouton "Go". LA liste montre les absences de l'élève pour chaque tranche horaire de chaque jour ou il a été absent. Une croix "x" rouge indique que l'élève a été absent pour la période correspondante.
</p>
HTML;

	// Parent & Élève.
else :

	$help['Attendance/DailySummary.php'] = <<<HTML
<p>
	<i>Tableau des Absences</i> est un rapport qui permet de consulter les statuts de présence de l'élève pour chaque jour d'une période de temps donnée.
</p>
<p>
	Vous pouvez modifier l'intervalle de temps en changeant les dates grâce aux menus déroulants en haut de l'écran et en cliquant sur le bouton "Go". La liste affiche la valeur de présence journalière de l'élève pour chaque jour avec des code de couleur. Le rouge signifie que l'élève a été absent toute la journée, le jaune qu'il a été absent une demi-journée et le vert qu'il a été présent toute la journée.
</p>
HTML;

endif;


// ELIGIBILITY ---.
if ( User( 'PROFILE' ) === 'admin' ) :

	$help['Eligibility/Student.php'] = <<<HTML
<p>
	<i>Écran Élève</i> affiche les activités de l'élève et les notes d'éligibilité pour la période courante. Le programme vous permet aussi d'assigner ou retirer des activités à / de l'élève.
</p>
<p>
	Vous devez d'abord sélectionner un élève au moyen de l'écran de recherche "Trouver un Élève". Vous pouvez rechercher les élèves inscrits à un cours spécifique en cliquant sur le lien "Choisir" à côté de l'option de recherche "Cours" et en choisissant un cours depuis la fenêtre popup qui apparaît. Vous pouvez aussi recherchez des élèves inscrits à une certaine activité ou des élèves qui sont actuellement inéligibles.
</p>
<p>
	Pour assigner une activité à l'élève, sélectionnez l'activité désirée depuis le menu déroulant situé à côté de l'icône ajouter (+) et cliquez sur le bouton "Ajouter".
</p>
<p>
	Pour retirer une activité, cliquez sur the l'icône effacer (-) à côté de l'activité en question.
</p>
<p>
	Vous pouvez sélectionner l'intervalle de dates courant depuis le menu déroulant en haut de l'écran. Ces périodes de temps sont configurées dans le programme "Heures de Saisie".
</p>
HTML;

	$help['Eligibility/AddActivity.php'] = <<<HTML
<p>
	<i>Assigner une Activité</i> vous permet d'assigner une activité à un groupe d'élèves en une fois.
</p>
<p>
	Premièrement, recherchez des élèves. Notez que vous pouvez rechercher des élèves inscrits à une certaine activité ou cours. Depuis les résultats de recherche, vous pouvez sélectionner un ou plusieurs élèves. Vous pouvez sélectionner tous les élèves de la liste en cochant la case à cocher de l'en-tête de liste. Ensuite, sélectionnez l'activité à ajouter depuis le menu déroulant en haut de l'écran. Une fois les élèves et l'activité désirés sélectionnés, cliquez sur le bouton "Assigner Activité aux Élèves Sélectionnés" en haut de l'écran.
</p>
HTML;

	$help['Eligibility/Activities.php'] = <<<HTML
<p>
	<i>Activités</i> vous permet de configurer les activités de l'école.
</p>
<p>
	Pour ajouter une activité, remplissez les champs titre, date de début, et date de fin dans les champs vides en bas de la liste d'activités et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour modifier une activité, cliquez sur une information de l'activité, éditez sa valeur, et cliquez sur le bouton "Enregistrer".
</p>
<p>
	Pour supprimer une activité, cliquez sur l'icône effacer (-) à côté de l'activité que vous souhaitez supprimer. Vous devrez confirmer la suppression.
</p>
HTML;

	$help['Eligibility/EntryTimes.php'] = <<<HTML
<p>
	<i>Heures de Saisie</i> vous permet de configurer l'intervalle de temps hebdomadaire durant lequel les enseignants peuvent saisir l'éligibilité. Les enseignants doivent saisir l'éligibilité chaque semaine durant cet intervalle. Cet intervalle est utilisé dans le programme enseignant "Saisir l'Éligibilité", mais aussi dans la plupart des rapports d'éligibilité.
</p>
<p>
	Pour changer l'intervalle de temps, changez les limites inférieures et supérieures de l'intervalle et cliquez sur le bouton "Enregistrer".
</p>
HTML;

	$help['Eligibility/StudentList.php'] = <<<HTML
<p>
	<i>Liste des Élèves</i> est un rapport qui permet de consulter pour chaque cours la note d'éligibilité attribuée aux élèves.
</p>
<p>
	Après avoir cherché les élèves, vous pouvez spécifier la période d'éligibilité que vous désirez consulter. Ces périodes de temps sont configurées dans le programme "Heures de Saisie".
</p>
HTML;

	$help['Eligibility/TeacherCompletion.php'] = <<<HTML
<p>
	<i>État d'Avancement</i> est un rapport qui permet de consulter quels enseignants n'ont pas encore saisi l'éligibilité pour une période donnée. Ces intervalles de dates sont configurés dans le programme "Heures de Saisie".
</p>
<p>
	Les croix rouges indiquent qu'un enseignant n'a pas saisi l'éligibilité pour cette classe et durant cet intervalle de dates.
</p>
<p>
	Vous pouvez sélectionner l'intervalle de dates depuis le menu déroulant en haut de l'écran. Vous pouvez aussi n'afficher qu'une seule tranche horaire en choisissant cette tranche horaire grâce au menu déroulant en haut de l'écran. Après avoir choisi une période ou une tranche horaire, cliquez sur le bouton "Go" pour rafraîchir la liste avec les nouveaux paramètres.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'teacher' ) :

	$help['Eligibility/EnterEligibility.php'] = <<<HTML
<p>
	<i>Saisir l'Éligibilité</i> vous permet de saisir les notes d'éligibilité pour tous les élèves de votre classe courante. Par défaut, ce programme liste les élèves de votre première classe. Vous pouvez changer la classe courante au moyen du menu déroulant situé dans le menu latéral.
</p>
<p>
	Une fois la classe correcte sélectionnée, vous pouvez saisir les notes d'éligibilité en sélectionnant le code d'éligibilité correspondant à chaque élève. Une fois l'éligibilité saisie pour tous vos élèves, cliquez sur le bouton "Enregistrer" en haut de l'écran.
</p>
<p>
	Si vous utilisez le Carnet de Notes, RosarioSIS peut calculer automatiquement les notes d'éligibilité notes de chaque élève en cliquant sur lien "Utiliser les Notes du Carnet" au-dessus de la liste. En cliquant sur le lien, les notes d'éligibilité de chaque élève sont enregistrées et la liste rafraîchie.
</p>
<p>
	Vous devez saisir l'éligibilité chaque semaine durant la période de temps définie par l'administration de l'école.
</p>
HTML;

	// Parent & Élève.
else :

	$help['Eligibility/Student.php'] = <<<HTML
<p>
	<i>Écran Élève</i> affiche les activités de l'élève et les notes d'éligibilité de la période courante.
</p>
<p>
	Vous pouvez spécifier la période d'éligibilité que vous désirez consulter en choisissant la période de temps dans le menu déroulant en haut de l'écran. L'Éligibilité est saisie une fois par semaine.
</p>
HTML;

	$help['Eligibility/StudentList.php'] = <<<HTML
<p>
	<i>Liste des Élèves</i> est un rapport qui permet de consulter les notes d'éligibilité attribuées à l'élève pour chaque cours.
</p>
<p>
	Vous pouvez spécifier la période d'éligibilité que vous désirez consulter en choisissant la période de temps dans le menu déroulant en haut de l'écran et en cliquant sur le bouton "Go". L'Éligibilité est saisie une fois par semaine.
</p>
HTML;

endif;
