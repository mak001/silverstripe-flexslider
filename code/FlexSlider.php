<?php

class FlexSlider extends DataExtension
{
    /**
     * @var array
     */
    public static $db = array(
        'Animation' => "Enum('slide, fade', 'slide')",
        'Loop' => 'Boolean',
        'Animate' => 'Boolean',
        'ThumbnailNav' => 'Boolean',
        'SliderControlNav' => 'Boolean',
        'SliderDirectionNav' => 'Boolean',
        'CarouselControlNav' => 'Boolean',
        'CarouselDirectionNav' => 'Boolean',
        'CarouselItemWidth' => 'Int',
        'CarouselItemMargin' => 'Int',
        'CarouselThumbnailCt' => 'Int',
    );

    /**
     * @var array
     */
    public static $has_many = array(
        'Slides' => 'SlideImage',
    );

    /**
     *
     */
    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->owner->Loop = 1;
        $this->owner->Animate = 1;
        $this->owner->SliderControlNav = 1;
        $this->owner->SliderDirectionNav = 1;
        $this->owner->CarouselControlNav = 0;
        $this->owner->CarouselDirectionNav = 1;
        $this->owner->CarouselItemWidth = 196;
        $this->owner->CarouselItemMargin = 10;
        $this->owner->CarouselThumbnailCt = 4;
    }

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(array(
            'Animation',
            'Loop',
            'Animate',
            'ThumbnailNav',
            'SliderControlNav',
            'SliderDirectionNav',
            'CarouselControlNav',
            'CarouselDirectionNav',
            'CarouselItemWidth',
            'CarouselItemMargin',
            'CarouselThumbnailCt'
        ));

        // Slides
        if ($this->owner->ID) {
            $config = GridFieldConfig_RecordEditor::create();
            $config->addComponent(new GridFieldOrderableRows('SortOrder'));
            if (class_exists('GridFieldBulkUpload')) {
                $config->addComponent(new GridFieldBulkUpload());
                $config->addComponent(new GridFieldBulkManager());
            }
            $config->removeComponentsByType('GridFieldAddExistingAutocompleter');
            $config->removeComponentsByType('GridFieldDeleteAction');
            $config->addComponent(new GridFieldDeleteAction(false));
            $SlidesField = GridField::create('Slides', 'Slides', $this->owner->Slides()->sort('SortOrder'), $config);

            $slideTitle = $this->owner->stat('slide_tab_title') ? $this->owner->stat('slide_tab_title') : 'Slides';

            $fields->addFieldsToTab("Root.{$slideTitle}", array(
                HeaderField::create('SliderHD', 'Slides', 3),
                $SlidesField,
                ToggleCompositeField::create('ConfigHD', 'Slider Settings', array(
                    CheckboxField::create('Animate', 'Animate automatically'),
                    DropdownField::create('Animation', 'Animation option', $this->owner->dbObject('Animation')->enumValues()),
                    CheckboxField::create('Loop', 'Loop the carousel'),
                    CheckboxField::create('SliderControlNav', 'Show ControlNav'),
                    CheckboxField::create('SliderDirectionNav', 'Show DirectionNav'),
                    CheckboxField::create('ThumbnailNav', 'Thumbnail Navigation'),
                    DisplayLogicWrapper::create(
                        CheckboxField::create('CarouselControlNav', 'Show Carousel ControlNav'),
                        CheckboxField::create('CarouselDirectionNav', 'Show Carousel DirectionNav'),
                        TextField::create('CarouselItemWidth', 'Thumbnail width'),
                        TextField::create('CarouselItemMargin', 'Thumbnail margin'),
                        NumericField::create('CarouselThumbnailCt', 'Number of thumbnails')
                    )->displayIf('ThumbnailNav')->isChecked()->end()
                )),
            ));
        }
    }

    /**
     * @return DataList
     */
    public function SlideShow()
    {
        $owner = $this->owner;

        if (!($owner instanceof SiteTree)) {
            $this->getCustomScript();
        }

        return $this->owner->Slides()->filter(array('ShowSlide' => 1))->sort('SortOrder');
    }

    /**
     * add requirements to Page_Controller init()
     */
    public function contentcontrollerInit()
    {
        // only call custom script if page has Slides and DataExtension
        if (Object::has_extension($this->owner->ClassName, 'FlexSlider')) {
            if ($this->owner->SlideShow()->exists()) {
                $this->getCustomScript();
            }
        }
    }

    /**
     *
     */
    public function getCustomScript()
    {
        // Flexslider options
        $animate = ($this->owner->Animate) ? 'true' : 'false';
        $loop = ($this->owner->Loop) ? 'true' : 'false';
        $sync = ($this->owner->ThumbnailNav == true) ? "sync: '.carousel:eq('+index+')'," : '';
        $before = (method_exists($this->owner->ClassName, 'flexSliderBeforeAction'))
            ? $this->owner->flexSliderBeforeAction()
            : 'function(){}';
        $after = (method_exists($this->owner->ClassName, 'flexSliderAfterAction'))
            ? $this->owner->flexSliderAfterAction()
            : 'function(){}';
        $speed = (method_exists($this->owner->ClassName, 'setFlexSliderSpeed'))
            ? $this->owner->setFlexSliderSpeed()
            : 7000;

        Requirements::customScript("
            (function($) {
                $(document).ready(function(){
                    jQuery('.flexslider').each(function(index){
					 
                         if(jQuery('.carousel').eq(index).length) {
                             jQuery('.carousel').eq(index).flexslider({
                                animation: '" . $this->owner->Animation . "',
                                controlNav: " . $this->owner->obj('CarouselDirectionNav')->NiceAsBoolean() . ", 
                                directionNav: " . $this->owner->obj('CarouselDirectionNav')->NiceAsBoolean() . ",
                                animationLoop: " . $this->owner->obj('Loop')->NiceAsBoolean() . ",
                                slideshow: " . $this->owner->obj('Animate')->NiceAsBoolean() . ",
                                itemWidth: " . $this->owner->obj('CarouselItemWidth') . ",
                                pausePlay: false,
                                asNavFor: '.flexslider:eq('+index+')',
                                itemMargin: 10, //todo: editable in CMS
                                minItems: 4, //todo: editable in CMS
                                maxItems: 4, //todo: editable in CMS
                                move: 4 //todo: editable in CMS
                              });
                         }
 
                        if(jQuery('.flexslider').eq(index).length){
                            jQuery('.flexslider').eq(index).flexslider({
                                slideshow: " . $this->owner->obj('Animate')->NiceAsBoolean() . ",
                                animation: '" . $this->owner->Animation . "',
                                animationLoop: " . $this->owner->obj('Loop')->NiceAsBoolean() . ",
                                controlNav: " . $this->owner->obj('Animate')->NiceAsBoolean() . ",
                                directionNav: " . $this->owner->obj('Animate')->NiceAsBoolean() . ",
                                prevText: '',
                                nextText: '',
                                pauseOnAction: true,
                                pauseOnHover: true,
                                " . $sync . "
                                start: function(slider){
                                  $('body').removeClass('loading');
                                },
                                before: " . $before . ',
                                after: ' . $after . ',
                                slideshowSpeed: ' . $speed . ' 
                            });
                        }
                    })
                });
            }(jQuery));'
        );
    }

    /**
     *
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->owner->CarouselItemWidth) {
            $this->owner->CarouselItemWidth = 196;
        }

        if (!$this->owner->CarouselItemMargin) {
            $this->owner->CarouselItemMargin = 10;
        }

        if (!$this->owner->CarouselThumbnailCt) {
            $this->owner->CarouselThumbnailCt = 4;
        }

    }
}
