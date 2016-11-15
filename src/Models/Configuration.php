<?php

declare(strict_types = 1);

namespace unreal4u\TelegramBots\Models;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('databases')
            ->children()
                ->scalarNode('default_connection')->isRequired()->cannotBeEmpty()->defaultValue('mysql')->end()
                ->arrayNode('mysql')
                    ->children()
                        ->scalarNode('name')->defaultValue('mysqlStorage')->end()
                        ->scalarNode('driver')->defaultValue('pdo_mysql')->cannotBeEmpty()->end()
                        ->scalarNode('dbhost')->defaultValue('localhost')->cannotBeEmpty()->end()
                        ->integerNode('dbport')->min(1)->max(65534)->defaultValue(3306)->end()
                        ->scalarNode('dbuser')->cannotBeEmpty()->end()
                        ->scalarNode('dbpass')->cannotBeEmpty()->end()
                        ->scalarNode('dbname')->cannotBeEmpty()->end()
                        ->scalarNode('charset')->cannotBeEmpty()->defaultValue('utf8mb4')->end()
                        ->arrayNode('default_table_options')
                            ->children()
                                ->scalarNode('charset')->defaultValue('utf8mb4')->cannotBeEmpty()->end()
                                ->scalarNode('collate')->defaultValue('utf8mb4_unicode_ci')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->variableNode('extra_types')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
