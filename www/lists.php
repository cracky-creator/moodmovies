<?php

require 'includes/open.php';

$lists = getAllListsWithFilmCount($pdo, $userId);

?>

<section class="lists">

    <div class="lists-wrapper">

        <h3 class="lists__title">Mes listes</h3>

        <ul class="lists__list">

            <?php foreach($lists as $list) { ?>

                <li class="lists__list__el">

                    <a class="list__el__link" href="list.php?id=<?php echo $list['id']; ?>">

                        <img class="list-asset" src="<?php echo $list['asset']; ?>" alt="list image">

                        <div class="list-container">

                            <p class="list-title"><?php echo 'Films ' . mb_strtolower($list['name']); ?></p>

                            <?php if($list['film_count'] <= 1){ ?>

                                <p class="list-count"><?php echo $list['film_count'] . ' film'; ?></p>

                            <?php } else { ?>

                                <p class="list-count"><?php echo $list['film_count'] . ' films'; ?></p>

                            <?php } ?>

                        </div>

                    </a>

                </li>

            <?php } ?>

        </ul>

    </div>

</section>

<?php

require 'includes/close.php';

?>