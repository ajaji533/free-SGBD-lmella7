USE parking_management;

UPDATE TARIFICATIONS 
SET tarif_horaire = 3.50 
WHERE id_parking = 1 
AND CURDATE() BETWEEN debut_validite AND fin_validite;

UPDATE VEHICULES 
SET etat = 'Très bon', kilometrage = 46000 
WHERE id_vehicule = 1;

UPDATE STATIONNEMENTS 
SET heure_sortie = NOW() 
WHERE id_vehicule = 1 
AND heure_sortie IS NULL;

UPDATE PARKINGS 
SET capacite = 180 
WHERE id_parking = 1;

UPDATE COMMUNES 
SET nom_commune = 'Paris Centre' 
WHERE id_commune = 1;

UPDATE TARIFICATIONS 
SET fin_validite = '2024-12-31' 
WHERE id_tarif = 1;