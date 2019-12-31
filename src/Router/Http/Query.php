<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Router\Http;

use Laminas\Mvc\Router\Exception;
use Laminas\Mvc\Router\Http\RouteMatch;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\RequestInterface as Request;
use Traversable;

/**
 * Query route.
 *
 * @package    Laminas_Mvc_Router
 * @subpackage Http
 * @see        http://manuals.rubyonrails.com/read/chapter/65
 */
class Query implements RouteInterface
{

    /**
     * Default values.
     *
     * @var array
     */
    protected $defaults;

    /**
     * List of assembled parameters.
     *
     * @var array
     */
    protected $assembledParams = array();

    /**
     * Create a new wildcard route.
     *
     * @param array $defaults
     */
    public function __construct(array $defaults = array())
    {
        $this->defaults = $defaults;
    }

    /**
     * factory(): defined by RouteInterface interface.
     *
     * @see    Route::factory()
     * @param  array|Traversable $options
     * @throws Exception\InvalidArgumentException
     * @return Query
     */
    public static function factory($options = array())
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (!is_array($options)) {
            throw new Exception\InvalidArgumentException(__METHOD__ . ' expects an array or Traversable set of options');
        }


        if (!isset($options['defaults'])) {
            $options['defaults'] = array();
        }

        return new static($options['defaults']);
    }

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    Route::match()
     * @param  Request $request
     * @param  int|null $pathOffset
     * @return RouteMatch
     */
    public function match(Request $request, $pathOffset = null)
    {
        if (!method_exists($request, 'getQuery')) {
            return null;
        }

        $matches = $this->recursiveUrldecode($request->getQuery()->toArray());

        return new RouteMatch(array_merge($this->defaults, $matches));
    }

    /**
     * Recursively urldecodes keys and values from an array
     *
     * @param array $array
     * @return array
     */
    protected function recursiveUrldecode(array $array)
    {
        $matches = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $matches[urldecode($key)] = $this->recursiveUrldecode($value);
            } else {
                $matches[urldecode($key)] = urldecode($value);
            }
        }
        return $matches;
    }

    /**
     * assemble(): Defined by RouteInterface interface.
     * @see    Route::assemble()
     *
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = array(), array $options = array())
    {
        $mergedParams          = array_merge($this->defaults, $params);
        $this->assembledParams = array();

        if (isset($options['uri']) && count($mergedParams)) {
            foreach ($mergedParams as $key => $value) {
                $this->assembledParams[] = $key;
            }

            $options['uri']->setQuery($mergedParams);
        }

        // A query does not contribute to the path, thus nothing is returned.
        return '';
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    Route::getAssembledParams
     * @return array
     */
    public function getAssembledParams()
    {
        return $this->assembledParams;
    }
}
