<?php

require_once 'includes/open.php';

require_once 'actions/register_action.php';

$emotions = getAllEmotions();

$genres = getAllGenres();

?>

<section class="register">
    <div class="register__wrapper">

        <div class="register__wrapper-top">

            <p class="app-title">MoodMovies</p>
    
            <div class="register__progress-bar">
    
                <span class="bar">progress bar</span>
                <span class="bar">progress bar</span>
                <span class="bar">progress bar</span>
                <span class="bar">progress bar</span>
    
            </div>
    
            <div class="register__progress-counter">
                <p><span class="progress-counter-increment">0</span> / 4</p>
            </div>

        </div>

        <?php if(!empty($errors)): ?>
            <ul class="errors">
                <?php foreach($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" class="register__form form">

            <h3 class="form__title">Nouveau compte</h3>

            <div class="form__wrapper">

                <fieldset class="form__group">
    
                    <div class="form__el">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" placeholder="Entrez un pseudo" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
    
                    <div class="form__el">
                        <label for="email">Adresse mail</label>
                        <input type="email" id="email" name="email" placeholder="Entrez votre adresse mail" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
    
                    <div class="form__el">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Entrez un mot de passe" required>
                    </div>
    
                    <div class="form__el">
                        <label for="age">Âge</label>
                        <input type="number" id="age" name="age" placeholder="Entrez votre âge" min="10" max="120" required value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
                    </div>
    
                </fieldset>
    
                <fieldset class="form__group">
    
                    <p>Afin de nous aider à vous proposez des recommandations sur mesure, sélectionnez deux émotions que vous adorez ressentir devant un film.</p>
    
                    <p><span class="form__mood-increment">0</span> / 3</p>
    
                    <ul class="form__list">
                        <?php foreach ($emotions as $emotion){ ?>
    
                            <li class="form__list__el">
    
                                <input type="checkbox" id="<?php echo strtolower($emotion['name']); ?>" name="moods[]" value="<?php echo ($emotion['id']); ?>">
    
                                <label for="<?php echo strtolower($emotion['name']); ?>"><?php echo ($emotion['name']); ?></label>
    
                            </li>
    
                        <?php } ?>
                        
                    </ul>
                </fieldset>
    
                <fieldset class="form__group">
    
                    <p>A présent, sélectionnez vos 4 genres préférés.</p>
    
                    <p><span class="form__genre-increment">0</span> / 4</p>
    
                    <ul class="form__list">
                        <?php foreach ($genres as $genre){ ?>
    
                            <li class="form__list__el">
    
                                <input type="checkbox" id="<?php echo strtolower($genre['name']); ?>" name="genres[]" value="<?php echo ($genre['id']); ?>">
    
                                <label for="<?php echo strtolower($genre['name']); ?>"><?php echo ($genre['name']); ?></label>
    
                            </li>
    
                        <?php } ?>
                        
                    </ul>
                </fieldset>

                <fieldset class="form__group">
    
                    <p>Nom d'utilisateur : <span class="username"></span></p>

                    <p>Adresse mail : <span class="user-mail"></span></p>

                    <p>Age : <span class="user-age"></span> ans</p>

                    <div class="form__mood-list">

                        <p class="form__list-title">Emotions favorites :</p>

                    </div>


                    <div class="form__genre-list">

                        <p class="form__list-title">Genres favoris :</p>

                    </div>

                </fieldset>

            </div>

            
            <div class="form__nav">

                <a href="intro.php" class="form__link-return">
                    <svg class="btn-icon" width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21.4141 8.70703H1.41406M1.41406 8.70703L9.41406 0.707031M1.41406 8.70703L9.41406 16.707" stroke="white" stroke-width="2"/>
                    </svg>

                    <span>Retour</span>
                </a>
                
                <button type="button" class="form__btn-return">
                    <svg class="btn-icon" width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21.4141 8.70703H1.41406M1.41406 8.70703L9.41406 0.707031M1.41406 8.70703L9.41406 16.707" stroke="white" stroke-width="2"/>
                    </svg>

                    <span>Retour</span>
                </button>
                
                <button type="button" class="form__btn-next">
                    <span>Suivant</span>

                    <svg class="btn-icon" width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 8.70703H20M20 8.70703L12 0.707031M20 8.70703L12 16.707" stroke="white" stroke-width="2"/>
                    </svg>
                </button>
                
                <button type="submit" class="form__btn-next form__btn-submit hidden"><span>Terminer</span></button>

            </div>
        </form>
    </div>
</section>

<?php require 'includes/close.php'; ?>