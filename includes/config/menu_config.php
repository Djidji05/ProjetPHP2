<?php
/**
 * Configuration du menu principal
 * Ce fichier définit la structure du menu unique utilisé dans tout le système
 */

// Vérification de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupération du rôle de l'utilisateur
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isGestionnaire = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'gestionnaire';

/**
 * Structure du menu principal
 */
$menuConfig = [
    'main' => [
        'dashboard' => [
            'title' => 'Tableau de bord',
            'icon' => 'bi-grid',
            'url' => 'dashboard.php',
            'roles' => ['admin', 'gestionnaire', 'utilisateur']
        ]
    ],
    'gestion' => [
        'header' => 'Gestion',
        'items' => [
            'proprietaires' => [
                'title' => 'Propriétaires',
                'icon' => 'bi-people',
                'url' => 'gestion_proprietaires.php',
                'roles' => ['admin', 'gestionnaire']
            ],
            'locataires' => [
                'title' => 'Locataires',
                'icon' => 'bi-person-lines-fill',
                'url' => 'gestion_locataires.php',
                'roles' => ['admin', 'gestionnaire']
            ],
            'appartements' => [
                'title' => 'Appartements',
                'icon' => 'bi-house-door',
                'url' => 'gestion_appartements.php',
                'roles' => ['admin', 'gestionnaire']
            ],
            'contrats' => [
                'title' => 'Contrats',
                'icon' => 'bi-file-earmark-text',
                'url' => 'gestion_contrats.php',
                'roles' => ['admin', 'gestionnaire']
            ],
            'paiements' => [
                'title' => 'Paiements',
                'icon' => 'bi-cash-coin',
                'url' => 'gestion_paiements.php',
                'roles' => ['admin', 'gestionnaire']
            ]
        ]
    ],
    'admin' => [
        'header' => 'Administration',
        'roles' => ['admin', 'gestionnaire'],
        'items' => [
            'utilisateurs' => [
                'title' => 'Utilisateurs',
                'icon' => 'bi-people',
                'items' => [
                    'ajouter_utilisateur' => [
                        'title' => 'Ajouter un utilisateur',
                        'url' => 'ajouter_utilisateur.php',
                        'roles' => ['admin']
                    ],
                    'gerer_utilisateurs' => [
                        'title' => 'Gérer les utilisateurs',
                        'url' => 'gestion_utilisateurs.php',
                        'roles' => ['admin']
                    ]
                ]
            ]
        ]
    ],
    'parametres' => [
        'header' => 'Paramètres',
        'items' => [
            'parametres' => [
                'title' => 'Paramètres',
                'icon' => 'bi-gear',
                'url' => 'parametres.php',
                'roles' => ['admin', 'gestionnaire', 'utilisateur']
            ],
            'deconnexion' => [
                'title' => 'Déconnexion',
                'icon' => 'bi-box-arrow-right',
                'url' => 'logout.php',
                'class' => 'text-danger',
                'roles' => ['admin', 'gestionnaire', 'utilisateur']
            ]
        ]
    ]
];

/**
 * Vérifie si un utilisateur a accès à un élément de menu
 * @param array $item L'élément de menu à vérifier
 * @return bool True si l'utilisateur a accès, false sinon
 */
function hasAccess($item) {
    global $isAdmin, $isGestionnaire;
    
    // Si pas de restriction de rôle, tout le monde a accès
    if (empty($item['roles'])) {
        return true;
    }
    
    // Vérification des rôles
    $userRole = 'utilisateur'; // Rôle par défaut
    if ($isAdmin) $userRole = 'admin';
    elseif ($isGestionnaire) $userRole = 'gestionnaire';
    
    return in_array($userRole, (array)$item['roles']);
}
