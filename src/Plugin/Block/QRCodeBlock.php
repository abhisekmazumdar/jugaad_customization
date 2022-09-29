<?php

namespace Drupal\jugaad_customization\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an QRCode block.
 *
 * @Block(
 *   id = "jugaad_customization_qrcode",
 *   admin_label = @Translation("QR Code"),
 *   category = @Translation("Jugaad Customization")
 * )
 */
class QRCodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Creates a LocalActionsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = $this->routeMatch->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $productLink = NULL;
    $build = [];

    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      $linkField = $node->get('field_app_purchase_link')->getValue();
      $productLink = array_shift($linkField)['uri'];
    }

    if ($productLink) {
      $result = Builder::create()
        ->writer(new PngWriter())
        ->data($productLink)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
        ->size(300)
        ->margin(10)
        ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
        ->build();

      $build['description'] = [
        '#markup' => '<h3>Scan here on your mobile</h3><p>To purchase this product on our app to avail exclusive app-only</p>',
      ];
      $build['qr-code'] = [
        '#type' => 'html_tag',
        '#tag' => 'img',
        '#attributes' => [
          'src' => $result->getDataUri(),
        ],
      ];
    }
    return $build;
  }

}
