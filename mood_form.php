<?php

    include 'functions/functions.php';

    $emotions = getEmotionsList();

    $intentions = getIntentionsList();

    $styles = getStylesList();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoodMovies Formulaire</title>
</head>
<body>

    <section>

        <form action="index.php" method="get" class="mood_form">

            <fieldset class="mood_form__emotion">

                <h3 class="mood_form__question">Quel est ton mood actuellement ?</h3>

                <ul class="mood_form__list">

                <?php foreach ($emotions as $emotion){ ?>

                    <li class="mood_form__el">

                        <label for="user_emotion"><p><?php echo $emotion ?></p></label>
    
                        <input type="radio" id="user_emotion" name="user_emotion[]" value="<?php echo $emotion ?>">

                    </li>

                <?php } ?>

                </ul>

            </fieldset>

            <fieldset class="mood_form__intention">

                <h3 class="mood_form__question">Pourquoi souhaites-tu regarder un film ?</h3>

                <ul class="mood_form__list">

                <?php foreach ($intentions as $intention){ ?>

                    <li class="mood_form__el">

                        <label for="user_intention"><p><?php echo $intention ?></p></label>
    
                        <input type="radio" id="user_intention" name="user_intention[]" value="<?php echo $intention ?>">

                    </li>

                <?php } ?>

                </ul>

            </fieldset>

            <fieldset class="mood_form__style">

                <h3 class="mood_form__question">Quel style de film souhaites-tu découvrir ?</h3>

                <ul class="mood_form__list">

                <?php foreach ($styles as $style){ ?>

                    <li class="mood_form__el">

                        <label for="user_style"><p><?php echo $style ?></p></label>
    
                        <input type="radio" id="user_style" name="user_style[]" value="<?php echo $style ?>">

                    </li>

                <?php } ?>

                </ul>

            </fieldset>

            <button type="submit" class="mood_form__btn">Lancer la recherche</button>

        </form>

    </section>

</body>
</html>