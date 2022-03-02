<?php
/**
 * Created by Loïc HALL <lhall@adexos.fr>
 */

namespace Chronopost\Chronorelais\Plugin;


use Magento\Framework\App\RequestInterface;

class ConfigPlugin
{

    protected $request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->request = $request;
    }

    /**
     * Transform virtual form data to JSON string
     *
     * @param \Magento\Config\Model\Config $subject
     * @param \Closure                     $proceed
     *
     * @return mixed
     */
    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {
        if ($subject->getSection() === 'chronorelais') {
            $this->request->getParams();
        }
        return $proceed();
    }
}
