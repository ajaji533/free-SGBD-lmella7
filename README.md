# Instructions d'installation et d'exécution

Ce projet utilise :
- PostgreSQL pour la base de données
- PHP (serveur embarqué) pour l’application web


## 1. Installation de PostgreSQL


sudo apt update
sudo apt install postgresql postgresql-contrib


## 2.Création de l’utilisateur PostgreSQL et de la base

sudo -u postgres psql
CREATE USER parking_user WITH PASSWORD 'votre mot de passe';

CREATE DATABASE parking_db OWNER parking_user;

\q

## 3.Chargement des scripts SQL du projet

psql -d parking_db


\i sql/create.sql


\i sql/insert.sql


\i sql/select.sql


\i sql/update.sql

Pour réinitialiser la base complétement:
\i sql/drop.sql

Puis :

\i sql/create.sql
\i sql/insert.sql
\q

4.Lancer l'application PHP en local

Dans le fichier src/db.php, vérifier que les paramètres de connexion correspondent
à l’utilisateur et à la base créés ci-dessus


php -S localhost:8000 -t src

Puis ouvrir un navigateur et aller sur l’adresse :
http://localhost:8000/


L’application est maintenant accessible en local.
