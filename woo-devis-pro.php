<?php
/**
 * Plugin Name: WooCommerce Demande de Devis Pro
 * Description: Transforme le panier en demande de devis pour certaines catégories avec interface de configuration et e-mails.
 * Version: 2.2
 * Author: Kini KONIN
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Sécurité

// --------------------------------------------------------
// 1. CRÉATION DU STATUT DE COMMANDE "DEMANDE DE DEVIS"
// --------------------------------------------------------
add_action('init', 'wcd_enregistrer_statut_devis');
function wcd_enregistrer_statut_devis() {
    register_post_status('wc-devis', array(
        'label'                     => 'Demande de devis',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Demande de devis <span class="count">(%s)</span>', 'Demandes de devis <span class="count">(%s)</span>')
    ));
}

add_filter('wc_order_statuses', 'wcd_ajouter_statut_liste');
function wcd_ajouter_statut_liste($order_statuses) {
    $new_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_statuses['wc-devis'] = 'Demande de devis';
        }
    }
    return $new_statuses;
}

// --------------------------------------------------------
// 2. FORCER L'ENVOI DES E-MAILS WOOCOMMERCE (CORRIGÉ)
// --------------------------------------------------------
// On utilise un hook universel qui se déclenche dès que la commande passe en "devis"
add_action('woocommerce_order_status_devis', 'wcd_envoyer_emails_devis', 10, 2);

function wcd_envoyer_emails_devis( $order_id, $order = null ) {
    if ( ! $order ) {
        $order = wc_get_order( $order_id );
    }
    
    // Sécurité : On vérifie si l'e-mail a déjà été envoyé pour éviter les doublons
    if ( get_post_meta( $order_id, '_email_devis_envoye', true ) ) {
        return;
    }

    // Charger les classes d'e-mail de WooCommerce
    $mailer = WC()->mailer();
    $emails = $mailer->get_emails();

    // 1. Mail "Nouvelle Commande" pour l'Administrateur
    if ( isset( $emails['WC_Email_New_Order'] ) ) {
        $emails['WC_Email_New_Order']->trigger( $order_id, $order );
    }

    // 2. Mail "Commande en cours" pour le Client (Accusé de réception)
    if ( isset( $emails['WC_Email_Customer_Processing_Order'] ) ) {
        $emails['WC_Email_Customer_Processing_Order']->trigger( $order_id, $order );
    }

    // On enregistre dans la commande que l'e-mail a bien été envoyé
    $order->update_meta_data( '_email_devis_envoye', 'yes' );
    $order->save();
}

// --------------------------------------------------------
// 3. CRÉATION DE LA PASSERELLE ET DE L'INTERFACE D'ADMIN
// --------------------------------------------------------
add_action('plugins_loaded', 'wcd_init_passerelle');
function wcd_init_passerelle() {
    if (!class_exists('WC_Payment_Gateway')) return;

    if ( ! class_exists( 'WC_Passerelle_Devis' ) ) {
        
        class WC_Passerelle_Devis extends WC_Payment_Gateway {
            public function __construct() {
                $this->id                 = 'demande_devis';
                $this->has_fields         = false;
                $this->method_title       = 'Demande de devis';
                $this->method_description = 'Configuration du mode Demande de Devis pour votre boutique.';
                
                $this->init_form_fields();
                $this->init_settings();
                
                $this->title       = $this->get_option('title');
                $this->description = $this->get_option('description');
                
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            }

            public function init_form_fields() {
                $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                $options_cat = array();
                if (!is_wp_error($categories) && !empty($categories)) {
                    foreach ($categories as $cat) {
                        $options_cat[$cat->slug] = $cat->name;
                    }
                }

                $this->form_fields = array(
                    'enabled' => array(
                        'title'   => 'Activer/Désactiver',
                        'type'    => 'checkbox',
                        'label'   => 'Activer la demande de devis',
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title'   => 'Titre',
                        'type'    => 'text',
                        'default' => 'Demande de devis',
                    ),
                    'description' => array(
                        'title'   => 'Description côté client',
                        'type'    => 'textarea',
                        'default' => 'Soumettez votre demande, nous vous reviendrons avec un devis détaillé.',
                    ),
                    'categories_cibles' => array(
                        'title'       => 'Catégories concernées',
                        'type'        => 'multiselect',
                        'class'       => 'wc-enhanced-select',
                        'description' => 'Sélectionnez les catégories qui déclenchent le mode devis.',
                        'options'     => $options_cat,
                        'desc_tip'    => true,
                    ),
                    'masquer_prix' => array(
                        'title'       => 'Masquer le prix',
                        'type'        => 'checkbox',
                        'label'       => 'Remplacer le prix par "Sur devis" pour ces catégories.',
                        'default'     => 'no'
                    ),
                    'texte_bouton' => array(
                        'title'       => 'Texte du bouton',
                        'type'        => 'text',
                        'default'     => 'Ajouter au devis',
                        'description' => 'Remplace le bouton "Ajouter au panier".'
                    )
                );
            }

            public function process_payment($order_id) {
                $order = wc_get_order($order_id);
                // Le passage à ce statut déclenche maintenant la fonction d'envoi d'e-mails
                $order->update_status('devis', 'Demande de devis soumise par le client.');
                WC()->cart->empty_cart();
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }
        }
    }
}

add_filter('woocommerce_payment_gateways', 'wcd_ajouter_passerelle');
function wcd_ajouter_passerelle($gateways) {
    $gateways[] = 'WC_Passerelle_Devis';
    return $gateways;
}

// --------------------------------------------------------
// 4. FONCTION POUR RÉCUPÉRER LES RÉGLAGES FACILEMENT
// --------------------------------------------------------
function wcd_get_reglages() {
    return get_option('woocommerce_demande_devis_settings', array());
}

// --------------------------------------------------------
// 5. RESTREINDRE LE DEVIS AUX CATÉGORIES CHOISIES
// --------------------------------------------------------
add_filter('woocommerce_available_payment_gateways', 'wcd_filtrer_passerelle_par_categorie');
function wcd_filtrer_passerelle_par_categorie($available_gateways) {
    if (is_admin() && !defined('DOING_AJAX')) return $available_gateways;
    if (empty(WC()->cart)) return $available_gateways;

    $reglages = wcd_get_reglages();
    $categories = isset($reglages['categories_cibles']) ? (array) $reglages['categories_cibles'] : array();
    
    if (empty($categories)) return $available_gateways;

    $contient_devis = false;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (has_term($categories, 'product_cat', $cart_item['product_id'])) {
            $contient_devis = true;
            break;
        }
    }

    if ($contient_devis) {
        $passerelle = isset($available_gateways['demande_devis']) ? $available_gateways['demande_devis'] : false;
        $available_gateways = array();
        if ($passerelle) $available_gateways['demande_devis'] = $passerelle;
    } else {
        if (isset($available_gateways['demande_devis'])) {
            unset($available_gateways['demande_devis']);
        }
    }
    return $available_gateways;
}

// --------------------------------------------------------
// 6. CHANGER LE TEXTE DU BOUTON DYNAMIQUEMENT
// --------------------------------------------------------
add_filter('woocommerce_product_single_add_to_cart_text', 'wcd_texte_bouton', 10, 2);
add_filter('woocommerce_product_add_to_cart_text', 'wcd_texte_bouton', 10, 2);
function wcd_texte_bouton($text, $product) {
    $reglages = wcd_get_reglages();
    $categories = isset($reglages['categories_cibles']) ? (array) $reglages['categories_cibles'] : array();
    $nouveau_texte = isset($reglages['texte_bouton']) && !empty($reglages['texte_bouton']) ? $reglages['texte_bouton'] : 'Ajouter au devis';

    if (!empty($categories) && has_term($categories, 'product_cat', $product->get_id())) {
        return $nouveau_texte;
    }
    return $text;
}

// --------------------------------------------------------
// 7. MASQUER LE PRIX (OPTIONNEL VIA LES RÉGLAGES)
// --------------------------------------------------------
add_filter('woocommerce_get_price_html', 'wcd_masquer_prix', 10, 2);
function wcd_masquer_prix($price, $product) {
    $reglages = wcd_get_reglages();
    $masquer = isset($reglages['masquer_prix']) ? $reglages['masquer_prix'] : 'no';
    $categories = isset($reglages['categories_cibles']) ? (array) $reglages['categories_cibles'] : array();

    if ($masquer === 'yes' && !empty($categories) && has_term($categories, 'product_cat', $product->get_id())) {
        return '<strong>Sur devis</strong>';
    }
    return $price;
}