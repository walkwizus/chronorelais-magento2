<?php
namespace Chronopost\Chronorelais\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Date extends Field
{
    /**
     * @var \Chronopost\Chronorelais\Model\Config\Source\Day
     */
    protected $_sourceDay;

    /**
     * @var \Chronopost\Chronorelais\Model\Config\Source\Hour
     */
    protected $_sourceHour;

    /**
     * @var \Chronopost\Chronorelais\Model\Config\Source\Minute
     */
    protected $_sourceMinute;


    /**
     * Date constructor.
     * @param Context $context
     * @param array $data
     * @param \Chronopost\Chronorelais\Model\Config\Source\Day $day
     * @param \Chronopost\Chronorelais\Model\Config\Source\Hour $hour
     * @param \Chronopost\Chronorelais\Model\Config\Source\Minute $minute
     */
    public function __construct(
        Context $context,
        \Chronopost\Chronorelais\Model\Config\Source\Day $day,
        \Chronopost\Chronorelais\Model\Config\Source\Hour $hour,
        \Chronopost\Chronorelais\Model\Config\Source\Minute $minute,
        array $data = []
    ) {
        $this->_sourceDay = $day;
        $this->_sourceHour = $hour;
        $this->_sourceMinute = $minute;
        parent::__construct($context, $data);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setStyle('width:70px;')
            ->setName($element->getName() . '[]');

        if ($element->getValue()) {
            $values = explode(':', $element->getValue());
        } else {
            $values = array();
        }
        $date = $element->setValues($this->_sourceDay->toOptionArray())->setValue(isset($values[0]) ? $values[0] : null)->getElementHtml();
        $heure = $element->setValues($this->_sourceHour->toOptionArray())->setValue(isset($values[1]) ? $values[1] : null)->getElementHtml();
        $minutes = $element->setValues($this->_sourceMinute->toOptionArray())->setValue(isset($values[2]) ? $values[2] : null)->getElementHtml();
        return __('Date') . ' : ' . $date
        . ' '
        . __('Time') . ' : ' . $heure.' '.$minutes;
    }

}