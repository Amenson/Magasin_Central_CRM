<?php
/**
 * INSTALLATION & SETUP GUIDE
 * Steps to integrate the messaging system into Anon-ecommerce
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide d'Installation - Système de Messagerie</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        h1, h2, h3 {
            color: #2c3e50;
        }
        
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        
        .step {
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 15px 0;
            background: #ecf0f1;
        }
        
        .warning {
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin: 15px 0;
            background: #fadbd8;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <h1>💬 Guide d'Installation - Système de Messagerie Professionnel</h1>
    
    <div class="card">
        <h2>Table des matières</h2>
        <ol>
            <li>Prérequis</li>
            <li>Installation de la Base de Données</li>
            <li>Structure des Fichiers</li>
            <li>Intégration dans l'Application</li>
            <li>Configuration</li>
            <li>Pages Client</li>
            <li>Pages Admin</li>
            <li>Test et Déploiement</li>
        </ol>
    </div>
    
    <div class="card">
        <h2>1. Prérequis</h2>
        <ul>
            <li>PHP 7.4 ou supérieur</li>
            <li>MySQL 5.7 ou supérieur</li>
            <li>Base de données e-commerce existante</li>
            <li>Tables <code>users</code> et <code>admins</code> déjà créées</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>2. Installation de la Base de Données</h2>
        <p>Exécutez le script SQL suivant dans votre base de données:</p>
        <pre>mysql -u root -p ecommerce < messaging/database/messages_schema.sql</pre>
        
        <p>Ou copiez-collez le contenu du fichier <code>messaging/database/messages_schema.sql</code> dans phpMyAdmin.</p>
    </div>
    
    <div class="card">
        <h2>3. Structure des Fichiers</h2>
        <p>Assurez-vous que la structure suivante existe dans votre projet:</p>
        <pre>/messaging
  /admin
    index.php          (Tableau de bord admin)
    view.php           (Voir conversation - admin)
  /client
    index.php          (Inbox client)
    new.php            (Créer nouveau message)
    view.php           (Voir conversation - client)
  /api
    send_message.php
    get_messages.php
    get_conversations.php
    get_unread_count.php
    rate_conversation.php
    download_attachment.php
  /classes
    MessagingClass.php
  /config
    messaging_config.php
  /database
    messages_schema.sql
  /helpers
    EmailNotifier.php
  /uploads/
    (Les pièces jointes seront stockées ici)</pre>
    </div>
    
    <div class="card">
        <h2>4. Intégration dans l'Application</h2>
        
        <h3>🔗 4.1 Ajouter le lien dans le menu client</h3>
        <p>Dans votre fichier de header/navigation principal, ajoutez:</p>
        <pre>&lt;a href="/messaging/client/index.php"&gt;📬 Messagerie&lt;/a&gt;</pre>
        
        <h3>🔗 4.2 Ajouter le bouton "Contacter le support" dans la page produit</h3>
        <pre>&lt;a href="/messaging/client/new.php?category=product"&gt;
    Poser une question au support
&lt;/a&gt;</pre>
        
        <h3>🔗 4.3 Ajouter le lien dans le compte client</h3>
        <p>Dans <code>profile.php</code>, ajoutez un section pour les messages:</p>
        <pre>&lt;div class="account-section"&gt;
    &lt;h3&gt;📬 Support&lt;/h3&gt;
    &lt;a href="/messaging/client/index.php"&gt;Voir mes messages&lt;/a&gt;
&lt;/div&gt;</pre>
    </div>
    
    <div class="card">
        <h2>5. Configuration</h2>
        <p>Modifiez le fichier <code>messaging/config/messaging_config.php</code>:</p>
        
        <table>
            <tr>
                <th>Paramètre</th>
                <th>Description</th>
                <th>Valeur par défaut</th>
            </tr>
            <tr>
                <td><code>MESSAGING_UPLOAD_DIR</code></td>
                <td>Dossier pour les uploads</td>
                <td>/uploads/messages/</td>
            </tr>
            <tr>
                <td><code>MESSAGING_MAX_FILE_SIZE</code></td>
                <td>Taille max des fichiers</td>
                <td>10 MB</td>
            </tr>
            <tr>
                <td><code>MESSAGING_SEND_EMAILS</code></td>
                <td>Envoyer emails</td>
                <td>true</td>
            </tr>
            <tr>
                <td><code>MESSAGING_ADMIN_EMAIL</code></td>
                <td>Email support</td>
                <td>admin@anon-ecommerce.com</td>
            </tr>
        </table>
    </div>
    
    <div class="card">
        <h2>6. Pages Client</h2>
        
        <h3>📬 6.1 Messagerie (Inbox)</h3>
        <p><strong>URL:</strong> <code>/messaging/client/index.php</code></p>
        <p><strong>Fonctionnalités:</strong></p>
        <ul>
            <li>Liste toutes les conversations du client</li>
            <li>Filtre par statut</li>
            <li>Affiche les messages non lus</li>
            <li>Pagination</li>
            <li>Réinitégration avec la base de données</li>
        </ul>
        
        <h3>📏 6.2 Nouveau Message</h3>
        <p><strong>URL:</strong> <code>/messaging/client/new.php</code></p>
        <p><strong>Fonctionnalités:</strong></p>
        <ul>
            <li>Créer une nouvelle conversation</li>
            <li>Sélectionner une catégorie</li>
            <li>Définir la priorité</li>
            <li>Envoyer un message initial</li>
        </ul>
        
        <h3>🗣️ 6.3 Voir Conversation</h3>
        <p><strong>URL:</strong> <code>/messaging/client/view.php?id=CONVERSATION_ID</code></p>
        <p><strong>Fonctionnalités:</strong></p>
        <ul>
            <li>Voir les messages de la conversation</li>
            <li>Répondre au support</li>
            <li>Voir le statut de la conversation</li>
            <li>Télécharger les pièces jointes</li>
            <li>Évaluer la conversation</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>7. Pages Admin</h2>
        
        <h3>⚙️ 7.1 Tableau de Bord</h3>
        <p><strong>URL:</strong> <code>/messaging/admin/index.php</code></p>
        <p><strong>Fonctionnalités:</strong></p>
        <ul>
            <li>Vue d'ensemble des statistiques</li>
            <li>Conversations par statut</li>
            <li>Conversation urgentes</li>
            <li>Conversations non assignées</li>
            <li>Filtrage avancé</li>
            <li>Recherche client/sujet</li>
        </ul>
        
        <h3>🗣️ 7.2 Gérer Conversation</h3>
        <p><strong>URL:</strong> <code>/messaging/admin/view.php?id=CONVERSATION_ID</code></p>
        <p><strong>Fonctionnalités:</strong></p>
        <ul>
            <li>Répondre au client</li>
            <li>Changer le statut</li>
            <li>Assigner à un admin</li>
            <li>Voir les informations client</li>
            <li>Gérer les pièces jointes</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>8. Utilisation des APIs</h2>
        
        <h3>🔗 8.1 Envoyer un Message</h3>
        <pre>POST /messaging/api/send_message.php
Content-Type: multipart/form-data

conversation_id: 1
message: "Votre message ici"
attachments: [files]</pre>
        
        <h3>🔗 8.2 Récupérer les Messages</h3>
        <pre>GET /messaging/api/get_messages.php?conversation_id=1&page=1</pre>
        
        <h3>🔗 8.3 Récupérer les Conversations</h3>
        <pre>GET /messaging/api/get_conversations.php?status=open&page=1</pre>
        
        <h3>🔗 8.4 Nombre de Messages Non Lus</h3>
        <pre>GET /messaging/api/get_unread_count.php</pre>
        
        <h3>🔗 8.5 Évaluer une Conversation</h3>
        <pre>POST /messaging/api/rate_conversation.php

conversation_id: 1
rating: 5
comment: "Excellent support!"</pre>
    </div>
    
    <div class="warning">
        <h2>⚠️ Points Importants de Sécurité</h2>
        <ul>
            <li><strong>Vérification d'accès:</strong> Vérifiez que l'utilisateur a accès à la conversation avant de l'afficher</li>
            <li><strong>Validation des uploads:</strong> Vérifiez le type et la taille des fichiers</li>
            <li><strong>Protection XSS:</strong> Utilisez <code>htmlspecialchars()</code> pour l'affichage</li>
            <li><strong>Protection CSRF:</strong> Implémentez des tokens CSRF</li>
            <li><strong>Répertoire uploads:</strong> Assurez-vous qu'il n'est pas accessible directement par le web</li>
            <li><strong>Permission fichiers:</strong> Définissez les bonnes permissions (755 pour dossiers, 644 pour fichiers)</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>9. Test et Déploiement</h2>
        
        <div class="step">
            <h3>🔍 9.1 Tests Locaux</h3>
            <ol>
                <li>Accédez à <code>http://localhost/messaging/client/new.php</code></li>
                <li>Créez un nouveau message</li>
                <li>Vérifiez qu'il apparaît dans l'inbox</li>
                <li>Répondez avec un compte admin</li>
                <li>Vérifiez que la réponse apparaît pour le client</li>
            </ol>
        </div>
        
        <div class="step">
            <h3>📄 9.2 Check-list Pré-Déploiement</h3>
            <ul>
                <li>☐ Base de données mise à jour</li>
                <li>☐ Dossier <code>/uploads/messages/</code> créé avec les bonnes permissions</li>
                <li>☐ Config email mise à jour</li>
                <li>☐ Test d'upload de fichiers</li>
                <li>☐ Test de création de conversation</li>
                <li>☐ Test du panneau admin</li>
                <li>☐ Vérification des permissions fichiers</li>
                <li>☐ Emails de test envoyés</li>
            </ul>
        </div>
    </div>
    
    <div class="card">
        <h2>🙋 Support</h2>
        <p>En cas de problème, vérifiez:</p>
        <ol>
            <li>Les logs PHP: <code>php_errors.log</code></li>
            <li>Les logs MySQL</li>
            <li>Les permissions des fichiers et dossiers</li>
            <li>La configuration de la base de données</li>
            <li>La session est correctement initialisée</li>
        </ol>
    </div>
</body>
</html>