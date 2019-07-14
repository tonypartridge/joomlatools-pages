<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerBehaviorValidatable extends KControllerBehaviorAbstract
{
    private $__rules;
    private $__valid_data;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__valid_data = null;
    }

    public function setValidationtRules(array $rules)
    {
        $this->__valid_data = null; //reset
        $this->__rules = $rules;

        return $this;
    }

    public function getValidationRules()
    {
        return (array) $this->__rules;
    }

    public function validateRequest()
    {
        if(is_null($this->__valid_data))
        {
            $data = $this->getRequest()->data;

            foreach($this->getValidationRules() as $key => $filters)
            {
                $filters = (array) $filters;

                //Check if field is required
                if(in_array('required', $filters))
                {
                    if(!$data->has($key) || empty($data->get($key, 'raw'))) {
                        throw new KControllerExceptionRequestInvalid(sprintf('%s is required', ucfirst($key)));
                    } else {
                       $filters = array_diff($filters, ['required']);
                    }
                }

                //Check if field is valid
                $value = $data->get($key, 'raw');
                $chain = $this->getObject('filter.factory')->createChain($filters);
                if(!$chain->validate($value)) {
                    throw new KControllerExceptionRequestInvalid(sprintf('%s is not valid', ucfirst($key)));
                }

                //Santize data just in case
                $this->__valid_data[$key] = $chain->sanitize($value);
            }
        }

        return $this->__valid_data;
    }

    public function isValidRequest()
    {
        return !empty($this->__valid_data);
    }

    public function isSupported()
    {
        $mixer   = $this->getMixer();
        $request = $mixer->getRequest();

        return $mixer->isDispatched() && $request->isFormSubmit();
    }
}