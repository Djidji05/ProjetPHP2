<?php
namespace anacaona;

use PDO;
use PDOException;
use DateTime;

class DashboardController {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::connect();
            error_log("Connexion à la base de données établie avec succès");
        } catch (Exception $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            throw $e;
        }
    }

    // Récupérer les statistiques principales
    public function getStats() {
        error_log("Début de la méthode getStats");
        $stats = [
            'total_revenus' => 0,
            'depenses_totales' => 0,
            'benefices' => 0,
            'nombre_locataires' => 0,
            'appartements_loues' => 0,
            'appartements_libres' => 0,
            'taux_occupation' => 0,
            'paiements_en_retard' => 0,
            'revenus_mois_courant' => 0,
            'depenses_mois_courant' => 0,
            'benefices_mois' => 0,
            'revenus_12_mois' => ['labels' => [], 'data' => []],
            'depenses_par_categorie' => ['labels' => [], 'data' => []],
            'occupation_par_immeuble' => ['labels' => [], 'data' => []]
        ];
        
        if (!$this->db) {
            error_log("Erreur: La connexion à la base de données n'est pas initialisée");
            return $stats;
        }

        try {
            // Nombre total de locataires
            $query = "SELECT COUNT(*) as total FROM locataires";
            $stmt = $this->db->query($query);
            $stats['nombre_locataires'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Appartements loués et taux d'occupation
            $query = "SELECT 
                        (SELECT COUNT(*) FROM appartements WHERE statut = 'loué') as loues,
                        (SELECT COUNT(*) FROM appartements) as total";
            $stmt = $this->db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['appartements_loues'] = (int)$result['loues'];
            $stats['taux_occupation'] = $result['total'] > 0 ? round(($result['loues'] / $result['total']) * 100) : 0;

            // Revenus du mois en cours
            $currentMonth = date('Y-m-01');
            $nextMonth = date('Y-m-01', strtotime('+1 month'));
            
            $query = "SELECT SUM(montant) as total FROM paiements 
                     WHERE date_paiement >= :debut_mois 
                     AND date_paiement < :fin_mois";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['debut_mois' => $currentMonth, 'fin_mois' => $nextMonth]);
            $stats['revenus_mois_courant'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Dépenses du mois en cours
            $query = "SELECT SUM(montant) as total FROM depenses 
                     WHERE date_depense >= :debut_mois 
                     AND date_depense < :fin_mois";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['debut_mois' => $currentMonth, 'fin_mois' => $nextMonth]);
            $stats['depenses_mois_courant'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Bénéfices du mois
            $stats['benefices_mois'] = $stats['revenus_mois_courant'] - $stats['depenses_mois_courant'];
            
            // Calculer le nombre d'appartements libres
            $query = "SELECT COUNT(*) as total FROM appartements WHERE statut = 'libre'";
            $stmt = $this->db->query($query);
            $stats['appartements_libres'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Paiements en retard (statut = 'retard')
            $query = "SELECT COUNT(*) as total FROM paiements 
                     WHERE statut = 'retard'";
            $stmt = $this->db->query($query);
            $stats['paiements_en_retard'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Données pour les graphiques
            $stats['revenus_12_mois'] = $this->getRevenus12DerniersMois();
            $stats['depenses_par_categorie'] = $this->getDepensesParCategorie();
            $stats['occupation_par_immeuble'] = $this->getOccupationParImmeuble();

        } catch (PDOException $e) {
            error_log("Erreur dans getStats: " . $e->getMessage());
        }

        return $stats;
    }

    // Récupérer les revenus des 12 derniers mois pour le graphique
    private function getRevenus12DerniersMois() {
        try {
            $data = [];
            $labels = [];
            $hasData = false;
            
            // Vérifier si la table paiements existe
            $tableExists = $this->db->query("SHOW TABLES LIKE 'paiements'");
            
            if ($tableExists->rowCount() === 0) {
                // La table n'existe pas, retourner des données vides
                for ($i = 11; $i >= 0; $i--) {
                    $date = new DateTime();
                    $date->modify("-$i months");
                    $labels[] = $date->format('M Y');
                    $data[] = 0;
                }
                
                return [
                    'labels' => $labels,
                    'data' => $data
                ];
            }
            
            for ($i = 11; $i >= 0; $i--) {
                $date = new DateTime();
                $date->modify("-$i months");
                $moisAnnee = $date->format('Y-m');
                $labels[] = $date->format('M Y');
                
                $debutMois = $date->format('Y-m-01');
                $finMois = $date->modify('+1 month')->format('Y-m-01');
                
                $query = "SELECT COALESCE(SUM(montant), 0) as total 
                         FROM paiements 
                         WHERE date_paiement >= :debut_mois 
                         AND date_paiement < :fin_mois";
                $stmt = $this->db->prepare($query);
                $stmt->execute(['debut_mois' => $debutMois, 'fin_mois' => $finMois]);
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $total = (float)$result['total'];
                $data[] = $total;
                
                if ($total > 0) {
                    $hasData = true;
                }
            }
            
            // Si aucune donnée n'a été trouvée pour les 12 derniers mois
            if (!$hasData) {
                $data = array_fill(0, 12, 0);
                $labels = [];
                $date = new DateTime();
                for ($i = 11; $i >= 0; $i--) {
                    $date = new DateTime();
                    $date->modify("-$i months");
                    $labels[] = $date->format('M Y');
                }
                
                return [
                    'labels' => $labels,
                    'data' => $data
                ];
            }
            
            return [
                'labels' => $labels,
                'data' => $data
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur dans getRevenus12DerniersMois: " . $e->getMessage());
            
            // En cas d'erreur, retourner des données vides pour éviter de casser le dashboard
            $labels = [];
            $date = new DateTime();
            for ($i = 11; $i >= 0; $i--) {
                $date = new DateTime();
                $date->modify("-$i months");
                $labels[] = $date->format('M Y');
            }
            
            return [
                'labels' => $labels,
                'data' => array_fill(0, 12, 0)
            ];
        }
    }

    // Récupérer les dépenses par catégorie
    private function getDepensesParCategorie() {
        try {
            // Vérifier si les tables existent
            $tablesExist = $this->db->query("SHOW TABLES LIKE 'depenses' AND SHOW TABLES LIKE 'categories_depenses'");
            
            if ($tablesExist->rowCount() < 2) {
                // Les tables n'existent pas encore, retourner des données vides
                return [
                    'labels' => ['Aucune donnée disponible'],
                    'data' => [0]
                ];
            }
            
            $query = "SELECT c.nom as categorie, COALESCE(SUM(d.montant), 0) as total 
                     FROM categories_depenses c
                     LEFT JOIN depenses d ON c.id = d.categorie_id 
                         AND d.date_depense >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY c.id, c.nom
                     HAVING total > 0
                     ORDER BY total DESC";
            
            $stmt = $this->db->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $labels = [];
            $data = [];
            
            if (empty($result) || (count($result) === 1 && $result[0]['categorie'] === null)) {
                // Aucune donnée trouvée
                return [
                    'labels' => ['Aucune dépense enregistrée'],
                    'data' => [1]  // Valeur par défaut pour afficher le graphique vide
                ];
            }
            
            foreach ($result as $row) {
                $labels[] = $row['categorie'];
                $data[] = (float)$row['total'];
            }
            
            return [
                'labels' => $labels,
                'data' => $data
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur dans getDepensesParCategorie: " . $e->getMessage());
            
            // En cas d'erreur, retourner des données vides pour éviter de casser le dashboard
            return [
                'labels' => ['Données non disponibles'],
                'data' => [0]
            ];
        }
    }

    // Récupérer le taux d'occupation par immeuble
    private function getOccupationParImmeuble() {
        $query = "SELECT 
                    i.nom as immeuble,
                    COUNT(a.id) as total_appartements,
                    SUM(CASE WHEN a.statut = 'loué' THEN 1 ELSE 0 END) as app_loues
                  FROM immeubles i
                  LEFT JOIN appartements a ON i.id = a.immeuble_id
                  GROUP BY i.id, i.nom";
        
        $stmt = $this->db->query($query);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $labels = [];
        $data = [];
        
        foreach ($result as $row) {
            $labels[] = $row['immeuble'];
            $taux = $row['total_appartements'] > 0 
                  ? round(($row['app_loues'] / $row['total_appartements']) * 100) 
                  : 0;
            $data[] = $taux;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    // Récupérer les dernières activités
    public function getActivitesRecentes($limit = 5) {
        // Récupérer les derniers paiements
        $query = "SELECT 
                    'paiement' as type,
                    p.date_paiement as date_activite,
                    CONCAT('Paiement de ', FORMAT(p.montant, 2, 'fr_FR'), ' € reçu de ', l.prenom, ' ', l.nom) as description,
                    p.id as reference_id
                 FROM paiements p
                 JOIN contrats c ON p.id_contrat = c.id
                 JOIN locataires l ON c.id_locataire= l.id
                 ORDER BY p.date_paiement DESC
                 LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si on n'a pas assez de paiements, ajouter des contrats récents
        if (count($activites) < $limit) {
            $remaining = $limit - count($activites);
            $query2 = "SELECT 
                        'contrat' as type,
                        c.date_debut as date_activite,
                        CONCAT('Nouveau contrat pour ', l.prenom, ' ', l.nom, ' - Appartement #', c.id_appartement) as description,
                        c.id as reference_id
                      FROM contrats c
                      JOIN locataires l ON c.id_locataire = l.id
                      ORDER BY c.date_debut DESC
                      LIMIT :remaining";
            
            $stmt2 = $this->db->prepare($query2);
            $stmt2->bindValue(':remaining', (int)$remaining, PDO::PARAM_INT);
            $stmt2->execute();
            
            $contrats = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            $activites = array_merge($activites, $contrats);
            
            // Trier toutes les activités par date décroissante
            usort($activites, function($a, $b) {
                return strtotime($b['date_activite']) - strtotime($a['date_activite']);
            });
            
            // Ne garder que le nombre demandé d'activités
            $activites = array_slice($activites, 0, $limit);
        }
        
        return $activites;
    }

    // Récupérer les paiements à venir (7 prochains jours)
    public function getPaiementsAvenir() {
        $query = "SELECT p.*, l.nom, l.prenom, 
                        CONCAT(l.prenom, ' ', l.nom) as locataire,
                        c.id_appartement
                 FROM paiements p
                 JOIN contrats c ON p.id_contrat = c.id
                 JOIN locataires l ON c.id_locataire = l.id
                 WHERE p.date_paiement BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 AND p.statut = 'paye'
                 ORDER BY p.date_paiement ASC";
        
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
