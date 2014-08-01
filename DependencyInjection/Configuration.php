<?php

namespace C33s\AttachmentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('c33s_attachment');

        $rootNode
            ->children()
            ->arrayNode('storages')
                ->prototype('array')
                    ->children()
                        ->scalarNode('filesystem')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('path_prefix')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('base_url')
                            ->defaultValue(null)
                        ->end()
                        ->scalarNode('base_path')
                            ->defaultValue(null)
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('attachments')
                ->children()
                    ->variableNode('hash_callable')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->defaultValue('sha1_file')
                    ->end()
                    ->integerNode('storage_depth')
                        ->min(0)
                        ->max(10)
                        ->defaultValue(3)
                    ->end()
                    ->scalarNode('storage')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    
                    ->arrayNode('models')
                        ->prototype('array')
                            ->children()
                                ->variableNode('hash_callable')
                                    ->cannotBeEmpty()
                                ->end()
                                ->integerNode('storage_depth')
                                    ->min(0)
                                    ->max(10)
                                ->end()
                                ->scalarNode('storage')
                                    ->cannotBeEmpty()
                                ->end()
                                
                                ->arrayNode('fields')
                                    ->prototype('array')
                                        ->children()
                                            ->variableNode('hash_callable')
                                                ->cannotBeEmpty()
                                            ->end()
                                            ->integerNode('storage_depth')
                                                ->min(0)
                                                ->max(10)
                                            ->end()
                                            ->scalarNode('storage')
                                                ->cannotBeEmpty()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
