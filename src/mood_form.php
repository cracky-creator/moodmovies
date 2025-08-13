<?php

    include 'functions/functions.php';

    $emotions = getEmotionsList();

    $intentions = getIntentionsList();

    $styles = getStylesList();

    include 'includes/open.php';

?>

<section>

    <form action="index.php" method="get" class="mood_form">

        <fieldset class="mood_form__emotion">

            <h3 class="mood_form__question">Quel est ton mood actuellement ?</h3>

            <select name="user_emotion[]" id="mood_form__emotion" class="mood_form__emotion">

                <option value="emotion"><p>Emotion</p></option>

            <?php foreach ($emotions as $emotion){ ?>

                <option id="user_emotion" value="<?php echo $emotion ?>"><p><?php echo $emotion ?></p></option>
                
            <?php } ?>

            </select>

        </fieldset>

        <fieldset class="mood_form__intention">

            <h3 class="mood_form__question">Pourquoi souhaites-tu regarder un film ?</h3>

            <select name="user_intention[]" id="mood_form__intention" class="mood_form__intention">

                <option value="intention"><p>Intention</p></option>

            <?php foreach ($intentions as $intention){ ?>

                <option id="user_intention" value="<?php echo $intention ?>"><p><?php echo $intention ?></p></option>
                
            <?php } ?>

            </select>

        </fieldset>

        <fieldset class="mood_form__style">

            <h3 class="mood_form__question">Quel style de film souhaites-tu découvrir ?</h3>

            <select name="user_style[]" id="mood_form__style" class="mood_form__style">

                <option value="style"><p>style</p></option>

            <?php foreach ($styles as $style){ ?>

                <option id="user_style" value="<?php echo $style ?>"><p><?php echo $style ?></p></option>
                
            <?php } ?>

            </select>

        </fieldset>

        <button type="submit" class="mood_form__btn">Lancer la recherche</button>

    </form>

</section>

<?php include 'includes/close.php'; ?>