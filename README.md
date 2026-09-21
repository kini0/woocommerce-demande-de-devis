# WooCommerce Demande de Devis Pro

Un plugin WordPress/WooCommerce léger et natif qui permet de transformer le processus d'achat classique en un système de demande de devis pour des catégories de produits spécifiques.

## 🚀 Fonctionnalités

- **Ciblage par catégorie** : Activez la demande de devis uniquement pour les catégories de produits de votre choix via une liste déroulante.
- **Statut de commande personnalisé** : Ajoute un nouveau statut "Demande de devis" dans votre tableau de bord WooCommerce pour bien séparer les devis des achats classiques.
- **Passerelle de paiement virtuelle** : Intercepte la validation du panier sans demander de carte bancaire.
- **Notifications par e-mail** : Envoie automatiquement un e-mail à l'administrateur (Nouvelle commande) et un accusé de réception au client.
- **Personnalisation de l'affichage** : 
  - Possibilité de masquer le prix des produits concernés (remplacé par un texte "Sur devis").
  - Modification automatique du texte du bouton "Ajouter au panier".
- **Interface d'administration native** : Zéro code requis pour la configuration. Tous les réglages se trouvent directement dans les paramètres de paiement de WooCommerce.

## 📥 Installation

1. Téléchargez le code source ou clonez ce dépôt :
   ```bash
   git clone https://github.com/votre-nom-utilisateur/woocommerce-demande-de-devis.git
   ```
2. Compressez le dossier en un fichier `.zip` (ou téléchargez directement le ZIP depuis GitHub).
3. Connectez-vous à votre administration WordPress.
4. Allez dans **Extensions > Ajouter**, cliquez sur **Téléverser une extension**.
5. Choisissez le fichier `.zip` et cliquez sur **Installer maintenant**.
6. **Activez** l'extension.

## ⚙️ Configuration

Une fois le plugin activé, la configuration se fait de manière très simple :

1. Dans WordPress, naviguez vers **WooCommerce > Réglages > Paiements**.
2. Repérez la ligne **Demande de devis** et activez-la grâce au commutateur (toggle).
3. Cliquez sur le nom **Demande de devis** (ou sur le bouton *Gérer* à droite) pour accéder aux réglages :
   - **Catégories concernées** : Sélectionnez les catégories qui doivent déclencher le mode devis.
   - **Masquer le prix** : Cochez cette case pour remplacer le prix par "Sur devis" sur les fiches produits.
   - **Texte du bouton** : Personnalisez le texte (ex: "Demander un devis").
4. Enregistrez les modifications.

## 💡 Comment ça marche ?

- Si un client ajoute un produit appartenant à une catégorie "Devis", toutes les autres passerelles de paiement (Stripe, PayPal, etc.) sont automatiquement masquées lors de la validation de la commande.
- Seule la passerelle "Demande de devis" reste disponible.
- Une fois le panier validé, la commande est enregistrée dans le back-office WooCommerce avec le statut **Demande de devis**, et le panier du client est vidé.

## 🤝 Contribution

Les contributions (issues, pull requests) sont les bienvenues pour améliorer ce plugin !

## 📄 Licence

Ce projet est sous licence MIT. Vous êtes libre de l'utiliser, de le modifier et de le distribuer.
