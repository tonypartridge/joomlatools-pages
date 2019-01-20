<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherRouterResolverPage extends ComPagesDispatcherRouterResolverHttp
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => self::PRIORITY_NORMAL,
            'routes'   => $this->getObject('page.registry')->getRoutes()
        ));

        parent::_initialize($config);
    }

    public function resolve(ComPagesDispatcherRouterInterface $router)
    {
        if($route = parent::resolve($router))
        {
            $response = $router->getResponse();
            $path     = $route->getPath();

            if($page = $this->getObject('page.registry')->getPage($path))
            {
                $query = array();
                if(isset($page->collection) && $page->collection !== false)
                {
                    //Set path
                    $query['path'] = $path;

                    //Set collection states
                    if(isset($page->collection->state)) {
                        $query = array_merge($query, KObjectConfig::unbox($page->collection->state));
                    }

                    //Set route states in page collection
                    foreach($route->query as $key => $value) {
                        $page->collection->state->set($key, $value);
                    }
                }
                else
                {
                    $segments = explode('/', $path);

                    //Set path and slug
                    $query['slug'] = array_pop($segments);
                    $query['path'] = implode($segments, '/') ?: '.';
                }

                //Set the params in the query overwriting existing values
                foreach($query as $key => $value) {
                    $response->getRequest()->query->set($key, $value);
                }
            }

            //Add a (self-referential) ccanonical URL using the first route for the specific page
            if($routes = $this->getObject('page.registry')->getRoutes($route->getPath()))
            {
                //Build the route
                $url = $this->buildRoute($routes[0],  $route->getQuery(true));

                //Qualify the url
                $url = $router->qualifyUrl($url);

                $router->setCanonicalUrl($url);
            }
        }

        return $route;
    }

    public function generate($page, array $query, ComPagesDispatcherRouterInterface $router)
    {
        if($url = parent::generate($page, $query, $router))
        {
            //Remove hardcoded collection states
            if($page = $this->getObject('page.registry')->getPage($page))
            {
                if(($collection = $page->isCollection()) && isset($collection['state'])) {
                    $url->query = array_diff_key($url->query, $collection['state']);
                }
            }

            ///Remove hardcoded model states
            unset($url->query['path']);
            unset($url->query['slug']);
            unset($url->query['view']);
        }

        return $url;
    }
}