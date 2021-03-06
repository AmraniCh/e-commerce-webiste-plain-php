<?php
	final class Client{
		private $con;
		
		public function __construct(){
			global $con;
			$this->con = $con;
		}
		
		public function AfficherClients(){
			$query = $this->con->query("SELECT * FROM client ORDER BY clientID DESC");
			if($query->num_rows > 0)
				return $query;
			
			return null;	
		}
		
		public function InfoClient(){
			
			$clientID = filter_var($_SESSION['clientID'], FILTER_SANITIZE_NUMBER_INT);
			
			$query = $this->con->query("SELECT * FROM client WHERE clientID = $clientID");
			
			if($query->num_rows > 0)
				return $query;
			
			return null;
		}
		
		public function PanierClient(){
			$clientID = filter_var($_SESSION['clientID'], FILTER_SANITIZE_NUMBER_INT);
			
			$query = $this->con->query("SELECT panierID FROM client WHERE clientID = $clientID");
			$row = $query->fetch_row();
			
			if($row[0] == null):
				// créer panier client 
				$insert = $this->con->query("INSERT INTO panier VALUES(null, default)");
				$select = $this->con->query("SELECT panierID FROM panier ORDER BY dateAjoute Desc");
				$row = $select->fetch_row();
				$update = $this->con->query("UPDATE client SET panierID = $row[0] WHERE clientID = $clientID");
				return $row[0];
			
			else:
				return $row[0];	
			endif;
			
			return null;
		}
		
		public function NbrArticlesPanier(){
			$clientID = filter_var($_SESSION['clientID'], FILTER_SANITIZE_NUMBER_INT);
			$query = $this->con->query("select count(*)
						from panierdetails pd 
						INNER join panier p
						on pd.panierID = p.panierID 
						inner join client c 
						on c.panierID = p.panierID 
						where c.clientID = $clientID");
			$row = $query->fetch_row();
            return $row[0];
		}
		
		public function ArticlePanierExiste($articleID, $qty, $couleur){
			$panierID = $this->PanierClient();
			$clientID = filter_var($_SESSION['clientID'], FILTER_SANITIZE_NUMBER_INT);
			
			$query = $this->con->query("select *
						FROM panierdetails pd 
						INNER JOIN panier p
						ON pd.panierID = p.panierID 
						INNER JOIN client c 
						ON c.panierID = p.panierID
						WHERE c.clientID = $clientID 
						AND pd.articleID = $articleID");
			$row = $query->fetch_assoc();
			
			if($this->con->affected_rows > 0):
			
				$nouvelle_qty = $row['quantite'] + $qty;
			
				$article = new Article();
				$verfie = $article->VerifierQuantite($articleID, $nouvelle_qty);
			
				if($verfie != null){
                    
                    $couleur = $article->CouleursArticle($articleID)[0];
                    if( $couleur != null)
                        $_couleur = $couleur;
                    else
                        $_couleur = 'N/A';
					
					$query = $this->con->query("UPDATE panierdetails 
												SET quantite = $nouvelle_qty,
                                                couleur = '$couleur'
												WHERE panierID = $panierID 
												AND articleID = $articleID");

					return true;
				}
				else
					return -1;
					
			endif;
			
			return null;
		}
        
        public function NbrArticlesFavoris($clientID){
			$query = $this->con->query("SELECT articleID,clientID FROM favoridetails WHERE clientID = $clientID GROUP BY articleID,clientID");
            return $this->con->affected_rows;
		}
                
        public function EmailValide($valide){
			if($valide == 0)
				return "<label class='badge badge-danger'>Pas validé</label>";
			else
				return "<label class='badge badge-success'>Validé</label>";
			return null;
        }
		
		public function ClientNomPrenom($clientID){
			
			$query = $this->con->query(" SELECT nom, prenom
										FROM client
										WHERE clientID = $clientID");
			
			if($query->num_rows > 0)
			{
				$row = $query->fetch_row();
				
				return strtoupper($row[0]).' '.strtoupper($row[1]);
			}
			
			return null;
		}
		
		public function InfoClientParCommande(){
			
            $query = $this->con->query(" SELECT clientID
                                    FROM commande
                                    WHERE commandeID = $commandeID
                                ");
            $row = $query->fetch_row();
            $clientID = $row[0];
			
			$query = $this->con->query(" SELECT * 
										FROM client
										WHERE clientID = $clientID
									");
			
			if($query->num_rows > 0)
				return $query;
			
			return null;
			
		}
	}

	final class Article{
			
		private $con;

		public function __construct()
		{
			global $con;
			$this->con = $con;
		}

		public function AfficherArticles(){
			
			$query = $this->con->query("SELECT * FROM article 
										WHERE articleDisponible = TRUE 
										ORDER BY dateAjoute DESC");
			
			if($query->num_rows > 0)
				return $query;
			
			return null;
		}

		public function ImageArticle($articleID){
			$query = $this->con->query("SELECT * 
								FROM imagearticle 
								WHERE articleID = $articleID 
								AND principale = 1");
            
			if($query->num_rows > 0){
                $row = $query->fetch_row();
				$image = '../uploaded/articles-images/'.$row[0];
            }
			else
				$image = '../index/img/not-founded4.png';
            
			return $image;
		}

		public function echoNiveau($articleID){
			$result = $this->con->query("SELECT niveau FROM article WHERE articleID = $articleID");
			$row = $result->fetch_row();
			$count = $row[0];
			$niveau = '';
			for($i=0;$i<$count;$i++) {
				$niveau=$niveau.'<i class="fa fa-star"></i>';
			}
			return $niveau;
		}

		public function ProduitsParCategorie($categorie){
			$result = $this->con->query(" SELECT * 
										FROM article inner join categorie
										on article.categorieID = categorie.categorieID
										where categorie.categorieNom = '$categorie' 
										AND article.articleDisponible = TRUE
										ORDER BY dateAjoute DESC"
									   );
			
			if($result->num_rows > 0)
					return $result;
			
			return null;
		}
		
		public function NouveauxProduitsAleatoire(){
			$result = $this->con->query(" SELECT * 
										FROM article 
										WHERE articleDisponible = TRUE 
										ORDER BY RAND()");
			
			if($result->num_rows > 0)
					return $result;
			
			return null;
		}

		public function ProduitsWidget($categorie){
			$result = $this->con->query("SELECT * FROM article inner join categorie
			on article.categorieID = categorie.categorieID
			where categorie.categorieNom = '$categorie' AND article.articleDisponible = TRUE ORDER BY RAND() LIMIT 3");
			if($result->num_rows > 0)
					return $result;
			return null;
		}
		
		public function RechercherArticle($categorieID, $mot){
			if($mot != ''){
				if($categorieID == 'tout')
					$result = $this->con->query("SELECT * FROM article WHERE articleNom LIKE '%$mot%' AND articleDisponible = TRUE ORDER BY dateAjoute DESC");
				else
					$result = $this->con->query("SELECT * FROM article inner join categorie
					on article.categorieID = categorie.categorieID
					where categorie.categorieID = '$categorieID' AND article.articleNom like '%$mot%' ORDER BY dateAjoute");
				if($result->num_rows > 0)
					return $result;
				return null;
			}
			return null;
	}
		
		public function NbrProduits(){
			$query = $this->con->query("SELECT COUNT(*)
									FROM article 
									WHERE articleDisponible = TRUE");
			$row = $query->fetch_row();
			return $row[0];	
		}
		
		public function NbrProduitsParCategorie($categorieID){
			$query = $this->con->query("SELECT COUNT(*) 
									FROM article 
									WHERE categorieID = $categorieID
									AND articleDisponible = TRUE");
			if($query->num_rows > 0)
			{
				$row = $query->fetch_row();
				return $row[0];
			}
			return null;
		}
		
		public function NbrProduitsParMarque($marque){
			$query = $this->con->query("SELECT COUNT(*) FROM article WHERE articleMarque = '$marque' AND articleDisponible = TRUE");
			$row = $query->fetch_row();
			return $row[0];
		}
		
		public function AfficherReviews($articleID, $limitRange){
			$query = $this->con->query("SELECT * FROM commentaire WHERE articleID = $articleID AND accepte = true ORDER BY dateComm DESC LIMIT $limitRange,3");
			if($query->num_rows > 0)
				return $query;
			return null;
		}
		
		public function NbrArticleReviews($articleID){
			$query = $this->con->query("SELECT COUNT(*) FROM commentaire WHERE accepte = TRUE AND articleID = $articleID");
			$row = $query->fetch_row();
			$nbr_reviews = $row[0];
			return $nbr_reviews;
		}
		
		public function NbrReviewsParNiveau($articleID){
			
			$data = array();
			
			for( $i = 1 ; $i <= 5 ; $i++)
			{
				$query = $this->con->query("SELECT COUNT(*) FROM commentaire WHERE accepte = TRUE AND niveau = $i ");
				
				$row = $query->fetch_row();
				
				$data[] = ['niveau'.$i => $row[0]];			
			}
			
			return $data;
		}
		
		public function ArticlePrix($articleID){
			$query = $this->con->query("SELECT a.*, p.* 
											FROM article a INNER JOIN panierdetails p
											ON a.articleID = p.articleID
											WHERE p.articleID = $articleID");
			$row = $query->fetch_assoc();

			if($query->num_rows > 0):
				if($row['remiseDisponible'])
					return $row['articlePrixRemise'] * $row['quantite'];
				else
					return $row['articlePrix'] * $row['quantite'];
			endif;
			
			return null;
		}
		
		public function ArticlesNoms($ids){
			
			$query = $this->con->query("SELECT articleNom FROM article WHERE articleID IN($ids)");
			
			if($query->num_rows > 0){
				$noms = "";
				while($row = $query->fetch_row()){

					$noms.= trim($row[0]).'|';

				}
				return substr($noms, 0, -1);
			}
		
			return null;
		}
		
		public function VerifierQuantite($articleID, $qty_commande){
			
			$query = $this->con->query("SELECT unitesEnStock FROM article WHERE articleID = $articleID");
			$row = $query->fetch_assoc();
			$qty_dispo = $row['unitesEnStock'];
			
			if($qty_commande <= $qty_dispo)
				return true;
				
			return null;
		}
		
		public function urlProduitParameterValue($articleID){
			global $con;

			$query = $con->query(" SELECT articleNom 
								FROM article 
								WHERE articleID = $articleID ");
			$row = $query->fetch_row();
			$articleNom = $row[0];
			
			$parameter_value = $articleID.'-'.strtolower(str_replace(' ', '-', $articleNom));
			
			return $parameter_value;
		}
        
        public function CouleursArticle($articleID){
            
            $couleurs = array();
            
            $query = $this->con->query("SELECT nomCouleur FROM couleurarticle WHERE articleID = $articleID");
        
            if( $query->num_rows > 0 ){
                
                while($row = $query->fetch_row()){
                    
                    $couleurs[] = $row[0];
                    
                }
                
                return $couleurs;
            }
            return null;
        }
		
		public function echoImages($articleID){
			$result = $this->con->query(" SELECT * 
										FROM imagearticle
										WHERE articleID = $articleID ");
			
			if($result->num_rows > 0){
            
				$images = '';
				while( $row = mysqli_fetch_array($result)){
				
					$image = '../uploaded/articles-images/'.$row['imageArticleNom'];
					
				 	$images = $images.' <div class="product-preview">
										<img src="'.$image.'"  alt="">
									</div> ';
				}
			}
			else
				$images = ' <div class="product-preview">
										<img src="../index/img/image-not-found.jpg"  alt="image-not-found.jpg">
									</div> ';

			return $images;
		} 

	}

	final class Categorie{
    	private $con;

    	function __construct(){
			global $con;
			$this->con = $con;
    	}

    	public function AfficherCategories(){
			$query = $this->con->query("SELECT * FROM categorie ORDER BY categorieID DESC");
			if($query->num_rows > 0)
				return $query;
			return null;
    	}

    	public function echoBadge($active){
			if($active == 0)
			return "<label class='badge badge-danger'>Pas Active</label>";
			else
			return "<label class='badge badge-success'>Active</label>";
			return null;
		}
		
		public function CategorieNomParID($categorieID){
			$query = $this->con->query("SELECT categorieNom FROM categorie WHERE categorieID = $categorieID");
			if($query->num_rows > 0):
				$row = $query->fetch_row();
				return $row[0];
			endif;
			return null;
		}
		
		public function CategoriesNoms($ids){
			
			$query = $this->con->query("SELECT categorieNom FROM categorie WHERE categorieID IN($ids)");
			
			if($query->num_rows > 0){
				$noms = "";
				while($row = $query->fetch_row()){

					$noms.= trim($row[0]).'|';

				}
				return substr($noms, 0, -1);
			}
		
			return null;
		}
        
        function RandomCategoriesNav(){
            $result = $this->con->query("SELECT categorieNom FROM categorie ORDER BY RAND() LIMIT 4");
            return $result;
        }
        
        function RandomCategoriesWidget(){
            $result = $this->con->query("SELECT categorieNom FROM categorie ORDER BY RAND() LIMIT 1");
            $row = $result->fetch_row();		
            return $row[0];
        }
	}
		
	final class Panier{
		private $con;

		public function __construct(){
			global $con;
			$this->con = $con;
		}

		public function AfficherPanierProduits(){
			$client = new Client();
			$panierID = $client->PanierClient();
			
			$query = $this->con->query("SELECT a.*, pd.*
						FROM panierdetails pd 
						INNER JOIN panier p 
						ON pd.panierID = p.panierID 
						INNER JOIN client c 
						ON c.panierID = p.panierID 
						INNER JOIN article a 
						ON a.articleID = pd.articleID
						WHERE pd.panierID = $panierID");
			if($query->num_rows > 0)
				return $query;
			return null;
		}
		
		public function ArticlesIDsPanier(){
			
			$client = new Client();
			$panierID = $client->PanierClient();
			
			$query = $this->con->query("SELECT articleID FROM panierdetails WHERE panierID = $panierID");
			
			if($query->num_rows > 0){
			
				$ids = "";
			
				while($row = $query->fetch_row()){
						
					$ids .= $row[0].',';
					
				}
				
				
				return substr($ids, 0, -1);
			
			}
			
			return "";
		}
		
	}

    final class Favori{
        private $con;

		public function __construct(){
			global $con;
			$this->con = $con;
		}
        
        public function AfficherProduitsFavoris(){
			$clientID = $_SESSION['clientID'];
            $query = $this->con->query("SELECT article.articleID, article.articleNom, article.articleDescription, article.articlePrix, article.articlePrixRemise, article.remiseDisponible, favoridetails.dateAjoute FROM article inner join favoridetails ON article.articleID = favoridetails.articleID WHERE favoridetails.clientID = $clientID GROUP BY article.articleID, article.articleNom, article.articleDescription, article.articlePrix, article.articlePrixRemise, article.remiseDisponible, favoridetails.dateAjoute ORDER BY favoridetails.dateAjoute DESC");
            if($query->num_rows > 0)
				return $query;
			return null;
        }
    }

	final class Coupon{
		
		private $con;
		
		public function __construct(){
			global $con;
			$this->con = $con;
		}
		
	 	public function AfficherCoupons(){

			$query = $this->con->query("SELECT * FROM coupon ORDER BY dateAjoute DESC");
			if($query->num_rows > 0)
				return $query;
			
			return null;
			
		}
		
		public function AppliquerAuMSJ($couponID ,$ids, $filter){
			
			$categorie = new Categorie();
			$article = new Article();
			
			switch($filter){
					
				case "tous":
					
					$this->con->query("UPDATE coupon SET appliquerAu = 'tous', filter = 'tous' WHERE couponID = $couponID");
					
				return true;
					
				case "categories":
					
					$noms = $categorie->CategoriesNoms($ids);
				
					$this->con->query("UPDATE coupon SET appliquerAu = '$noms', filter = 'categories' WHERE couponID = $couponID");
					
				return $noms;
					
				case "articles":
					
					$noms = $article->ArticlesNoms($ids);
				
					$this->con->query("UPDATE coupon SET appliquerAu = '$noms', filter = 'articles' WHERE couponID = $couponID");
					
				return true;
	
			}
			
			return null;
		}
		
	}

	final class Commande{
		
		private $con;
		
		public function __construct(){
			global $con;
			$this->con = $con;
		}
		
		public function AfficherCommandes(){
			
			$query = $this->con->query(" SELECT *
										FROM commande
										WHERE status = 0 OR status = 2 OR status = -2
										ORDER BY commandeDate DESC
										");
			
			if( $query->num_rows > 0 )
				return $query;
			
			return null;	
		}
		
		public function AfficherArtilcesCommande($commandeID){
			
			$query = $this->con->query(" SELECT *
										FROM article a INNER JOIN commandedetails cd
										ON a.articleID = cd.articleID
										WHERE cd.commandeID = $commandeID
										");
			
			if( $query->num_rows > 0 )
				return $query;
			
			return null;
		}
		
		public function VerifieCommandeID($commandeID){
			
			$clientID = filter_var($_SESSION['clientID'], FILTER_SANITIZE_NUMBER_INT);
			
			$query = $this->con->query(" SELECT commandeID 
										FROM commande 
										WHERE clientID = $clientID");
			if( $query->num_rows > 0 )
				return true;
			
			return null;
		}
		
	}

	final class Livraison{
		
		private $con;
		
		public function __construct(){
			global $con;
			$this->con = $con;
		}
		
		public function AfficherLivraisons(){
			
			$query = $this->con->query(" SELECT * 
										FROM livraison l INNER JOIN commande c
										ON l.commandeID = c.commandeID
										WHERE c.status = 1
										ORDER BY confirmationDate DESC
										");
			
			if( $query->num_rows > 0 )
				return $query;
			
			return null;
		}
		
	}

	final class Commentaire{
		private $con;
		
		public function __construct(){
			global $con;
			$this->con = $con;
		}
		
		public function AfficherCommentaires(){
			
			$query = $this->con->query(" SELECT * 
										FROM commentaire
										ORDER BY dateComm DESC ");
			if( $query->num_rows > 0 )
				return $query;
			
			return null;
		}
	}     
      