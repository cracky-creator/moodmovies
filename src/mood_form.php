<?php

    include 'functions/functions.php';

    $emotions = getEmotionsList();

    $intentions = getIntentionsList();

    $styles = getStylesList();

    include 'includes/open.php';

?>

<section class="app_introduction">

    <div class="app_introduction__content">

        <h1 class="app_title font-gradient">M<span class="double-o">oo</span>dMovies</h1>

        
        <form action="index.php" method="get" class="mood_form">

            <h4 class="mood_form__introduction">Bienvenue sur MoodMovies ! Décrivez nous votre mood, votre intention et le style de film que vous souhaitez découvrir afin de vous laissez surprendre par des films qui vous ressemblent.</h4>
            
            <fieldset class="mood_form__container">

                <div class="mood_form__el box-gradient">
        
                    <select name="user_emotion[]" id="mood_form__emotion" class="mood_form__reponse">
        
                        <option class="mood_form__reponse__el" value="emotion"><p>Mood</p></option>
        
                    <?php foreach ($emotions as $emotion){ ?>
        
                        <option class="mood_form__reponse__el" id="user_emotion" value="<?php echo $emotion ?>"><p><?php echo $emotion ?></p></option>
                        
                    <?php } ?>
        
                    </select>

                </div>
    
                <div class="mood_form__el box-gradient">
        
                    <select name="user_intention[]" id="mood_form__intention" class="mood_form__reponse">
        
                        <option class="mood_form__reponse__el" value="intention"><p>Intention</p></option>
        
                    <?php foreach ($intentions as $intention){ ?>
        
                        <option class="mood_form__reponse__el" id="user_intention" value="<?php echo $intention ?>"><p><?php echo $intention ?></p></option>
                        
                    <?php } ?>
        
                    </select>
    
                </div>

                <div class="mood_form__el box-gradient">
        
                    <select name="user_style[]" id="mood_form__style" class="mood_form__reponse">
        
                        <option class="mood_form__reponse__el" value="style"><p>style</p></option>
        
                    <?php foreach ($styles as $style){ ?>
        
                        <option class="mood_form__reponse__el" id="user_style" value="<?php echo $style ?>"><p><?php echo $style ?></p></option>
                        
                    <?php } ?>
        
                    </select>
    
                </div>

            </fieldset>
    
            <button type="submit" class="mood_form__btn box-gradient"><p>Lancer la recherche</p></button>
    
        </form>

    </div>

</section>

<?php include 'includes/close.php'; ?>