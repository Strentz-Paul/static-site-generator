<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>

class <?= $class_name; ?> extends AbstractController
{
<?= $generator->generateRouteForControllerMethod($route_path, $route_name); ?>
    public function <?= $method_name ?>(): <?php if ($with_template) { ?>Response<?php } else { ?>JsonResponse<?php } ?>

    {
        return $this->render('Pages/<?= ucfirst($template_name) ?>');
    }


<?= $generator->generateRouteForControllerMethod($route_path_slug, $route_name_slug); ?>
    public function <?= $method_name_slug ?>(string $slug): <?php if ($with_template) { ?>Response<?php } else { ?>JsonResponse<?php } ?>

    {
        return $this->render("Pages/<?= ucfirst($template_name_slug) ?>");
    }
}