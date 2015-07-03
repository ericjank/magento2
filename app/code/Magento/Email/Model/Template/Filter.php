<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Email\Model\Template;

/**
 * Core Email Template Filter Model
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Filter extends \Magento\Framework\Filter\Template
{
    /**
     * Use absolute links flag
     *
     * @var bool
     */
    protected $_useAbsoluteLinks = false;

    /**
     * Whether to allow SID in store directive: NO
     *
     * @var bool
     */
    protected $_useSessionInUrl = false;

    /**
     * Modifier Callbacks
     *
     * @var array
     */
    protected $_modifiers = ['nl2br' => ''];

    /**
     * Whether template being filtered is child of another template
     *
     * @var bool
     */
    private $isChildTemplate = false;

    /**
     * List of CSS files to inline
     *
     * @var []
     */
    private $inlineCssFiles = [];

    /**
     * Store id
     *
     * @var int
     */
    protected $_storeId = null;

    /**
     * @var array
     */
    private $designParams = [];

    /**
     * @var bool
     */
    private $plainTemplateMode = false;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper = null;

    /**
     * Core store config
     * Variable factory
     *
     * @var \Magento\Variable\Model\VariableFactory
     */
    protected $_variableFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $_layoutFactory;

    /**
     * Setup callbacks for filters
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Layout directive params
     *
     * @var array
     */
    protected $_directiveParams;

    /**
     * App state
     *
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrlBuilder;

    /**
     * @var \Pelago\Emogrifier
     */
    protected $emogrifier;

    /**
     * @param \Magento\Framework\Stdlib\String $string
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Variable\Model\VariableFactory $coreVariableFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Backend\Model\UrlInterface $backendUrlBuilder
     * @param \Pelago\Emogrifier $emogrifier
     * @param array $variables
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Stdlib\String $string,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Variable\Model\VariableFactory $coreVariableFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Backend\Model\UrlInterface $backendUrlBuilder,
        \Pelago\Emogrifier $emogrifier,
        $variables = []
    ) {
        $this->_escaper = $escaper;
        $this->_assetRepo = $assetRepo;
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_modifiers['escape'] = [$this, 'modifierEscape'];
        $this->_variableFactory = $coreVariableFactory;
        $this->_storeManager = $storeManager;
        $this->_layout = $layout;
        $this->_layoutFactory = $layoutFactory;
        $this->_appState = $appState;
        $this->backendUrlBuilder = $backendUrlBuilder;
        $this->emogrifier = $emogrifier;
        parent::__construct($string, $variables);
    }

    /**
     * Set use absolute links flag
     *
     * @param bool $flag
     * @return $this
     */
    public function setUseAbsoluteLinks($flag)
    {
        $this->_useAbsoluteLinks = $flag;
        return $this;
    }

    /**
     * Setter whether SID is allowed in store directive
     * Doesn't set anything intentionally, since SID is not allowed in any kind of emails
     *
     * @param bool $flag
     * @return $this
     */
    public function setUseSessionInUrl($flag)
    {
        $this->_useSessionInUrl = $flag;
        return $this;
    }

    /**
     * Setter
     *
     * @param bool $plainTemplateMode
     * @return $this
     */
    public function setPlainTemplateMode($plainTemplateMode)
    {
        $this->plainTemplateMode = (bool) $plainTemplateMode;
        return $this;
    }

    /**
     * Check whether template is plain
     *
     * @return bool
     */
    public function isPlainTemplateMode()
    {
        return $this->plainTemplateMode;
    }

    /**
     * Set whether template being filtered is child of another template
     *
     * @param bool $isChildTemplate
     * @return $this
     */
    public function setIsChildTemplate($isChildTemplate)
    {
        $this->isChildTemplate = (bool) $isChildTemplate;
        return $this;
    }

    /**
     * Get whether template being filtered is child of another template
     *
     * @return bool
     */
    public function isChildTemplate()
    {
        return $this->isChildTemplate;
    }

    /**
     * Setter
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * Set design parameters
     *
     * @param array $designParams
     * @return $this
     */
    public function setDesignParams(array $designParams)
    {
        $this->designParams = $designParams;
        return $this;
    }

    /**
     * Get design parameters
     *
     * @return array
     */
    public function getDesignParams()
    {
        return $this->designParams;
    }

    /**
     * Getter. If $_storeId is null, return design store id.
     *
     * @return integer
     */
    public function getStoreId()
    {
        if (null === $this->_storeId) {
            $this->_storeId = $this->_storeManager->getStore()->getId();
        }
        return $this->_storeId;
    }

    /**
     * Retrieve Block html directive
     *
     * @param array $construction
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function blockDirective($construction)
    {
        $skipParams = ['class', 'id', 'output'];
        $blockParameters = $this->_getParameters($construction[2]);
        $block = null;

        if (isset($blockParameters['class'])) {
            $block = $this->_layout->createBlock($blockParameters['class'], null, ['data' => $blockParameters]);
        } elseif (isset($blockParameters['id'])) {
            $block = $this->_layout->createBlock('Magento\Cms\Block\Block');
            if ($block) {
                $block->setBlockId($blockParameters['id']);
            }
        }

        if (!$block) {
            return '';
        }

        $block->setBlockParams($blockParameters);
        foreach ($blockParameters as $k => $v) {
            if (in_array($k, $skipParams)) {
                continue;
            }
            $block->setDataUsingMethod($k, $v);
        }

        if (isset($blockParameters['output'])) {
            $method = $blockParameters['output'];
        }
        if (!isset($method)
            || !is_string($method)
            || !method_exists($block, $method)
            || !is_callable([$block, $method])
        ) {
            $method = 'toHtml';
        }
        return $block->{$method}();
    }

    /**
     * Retrieve layout html directive
     *
     * @param string[] $construction
     * @return string
     */
    public function layoutDirective($construction)
    {
        $this->_directiveParams = $this->_getParameters($construction[2]);
        if (!isset($this->_directiveParams['area'])) {
            $this->_directiveParams['area'] = 'frontend';
        }
        if ($this->_directiveParams['area'] != $this->_appState->getAreaCode()) {
            return $this->_appState->emulateAreaCode(
                $this->_directiveParams['area'],
                [$this, 'emulateAreaCallback']
            );
        } else {
            return $this->emulateAreaCallback();
        }
    }

    /**
     * Retrieve layout html directive callback
     *
     * @return string
     */
    public function emulateAreaCallback()
    {
        $skipParams = ['handle', 'area'];

        /** @var $layout \Magento\Framework\View\LayoutInterface */
        $layout = $this->_layoutFactory->create(['cacheable' => false]);
        $layout->getUpdate()->addHandle($this->_directiveParams['handle'])->load();

        $layout->generateXml();
        $layout->generateElements();

        $rootBlock = false;
        foreach ($layout->getAllBlocks() as $block) {
            /* @var $block \Magento\Framework\View\Element\AbstractBlock */
            if (!$block->getParentBlock() && !$rootBlock) {
                $rootBlock = $block;
            }
            foreach ($this->_directiveParams as $k => $v) {
                if (in_array($k, $skipParams)) {
                    continue;
                }
                $block->setDataUsingMethod($k, $v);
            }
        }

        /**
         * Add root block to output
         */
        if ($rootBlock) {
            $layout->addOutputElement($rootBlock->getNameInLayout());
        }

        $result = $layout->getOutput();
        $layout->__destruct();
        // To overcome bug with SimpleXML memory leak (https://bugs.php.net/bug.php?id=62468)
        return $result;
    }

    /**
     * Retrieve block parameters
     *
     * @param mixed $value
     * @return array
     */
    protected function _getBlockParameters($value)
    {
        $tokenizer = new \Magento\Framework\Filter\Template\Tokenizer\Parameter();
        $tokenizer->setString($value);

        return $tokenizer->tokenize();
    }

    /**
     * Retrieve View URL directive
     *
     * @param string[] $construction
     * @return string
     */
    public function viewDirective($construction)
    {
        $params = $this->_getParameters($construction[2]);
        $url = $this->_assetRepo->getUrlWithParams($params['url'], $params);
        return $url;
    }

    /**
     * Retrieve media file URL directive
     *
     * @param string[] $construction
     * @return string
     */
    public function mediaDirective($construction)
    {
        $params = $this->_getParameters($construction[2]);
        return $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $params['url'];
    }

    /**
     * Retrieve store URL directive
     * Support url and direct_url properties
     *
     * @param string[] $construction
     * @return string
     */
    public function storeDirective($construction)
    {
        $params = $this->_getParameters($construction[2]);
        if (!isset($params['_query'])) {
            $params['_query'] = [];
        }
        foreach ($params as $k => $v) {
            if (strpos($k, '_query_') === 0) {
                $params['_query'][substr($k, 7)] = $v;
                unset($params[$k]);
            }
        }
        $params['_absolute'] = $this->_useAbsoluteLinks;

        if ($this->_useSessionInUrl === false) {
            $params['_nosid'] = true;
        }

        if (isset($params['direct_url'])) {
            $path = '';
            $params['_direct'] = $params['direct_url'];
            unset($params['direct_url']);
        } else {
            $path = isset($params['url']) ? $params['url'] : '';
            unset($params['url']);
        }

        return $this->getUrl($path, $params);
    }

    /**
     * @param string $path
     * @param array $params
     * @return string
     */
    protected function getUrl($path, $params)
    {
        $isBackendStore = \Magento\Store\Model\Store::DEFAULT_STORE_ID === $this->getStoreId()
            || \Magento\Store\Model\Store::ADMIN_CODE === $this->getStoreId();

        return $isBackendStore
            ? $this->backendUrlBuilder->getUrl($path, $params)
            : $this->_storeManager->getStore($this->getStoreId())->getUrl($path, $params);
    }

    /**
     * Directive for converting special characters to HTML entities
     * Supported options:
     *     allowed_tags - Comma separated html tags that have not to be converted
     *
     * @param string[] $construction
     * @return string
     */
    public function escapehtmlDirective($construction)
    {
        $params = $this->_getParameters($construction[2]);
        if (!isset($params['var'])) {
            return '';
        }

        $allowedTags = null;
        if (isset($params['allowed_tags'])) {
            $allowedTags = preg_split('/\s*\,\s*/', $params['allowed_tags'], 0, PREG_SPLIT_NO_EMPTY);
        }

        return $this->_escaper->escapeHtml($params['var'], $allowedTags);
    }

    /**
     * Var directive with modifiers support
     *
     * @param string[] $construction
     * @return string
     */
    public function varDirective($construction)
    {
        if (count($this->_templateVars) == 0) {
            // If template preprocessing
            return $construction[0];
        }

        $parts = explode('|', $construction[2], 2);
        if (2 === count($parts)) {
            list($variableName, $modifiersString) = $parts;
            return $this->_amplifyModifiers($this->_getVariable($variableName, ''), $modifiersString);
        }
        return $this->_getVariable($construction[2], '');
    }

    /**
     * Apply modifiers one by one, with specified params
     *
     * Modifier syntax: modifier1[:param1:param2:...][|modifier2:...]
     *
     * @param string $value
     * @param string $modifiers
     * @return string
     */
    protected function _amplifyModifiers($value, $modifiers)
    {
        foreach (explode('|', $modifiers) as $part) {
            if (empty($part)) {
                continue;
            }
            $params = explode(':', $part);
            $modifier = array_shift($params);
            if (isset($this->_modifiers[$modifier])) {
                $callback = $this->_modifiers[$modifier];
                if (!$callback) {
                    $callback = $modifier;
                }
                array_unshift($params, $value);
                $value = call_user_func_array($callback, $params);
            }
        }
        return $value;
    }

    /**
     * Escape specified string
     *
     * @param string $value
     * @param string $type
     * @return string
     */
    public function modifierEscape($value, $type = 'html')
    {
        switch ($type) {
            case 'html':
                return htmlspecialchars($value, ENT_QUOTES);

            case 'htmlentities':
                return htmlentities($value, ENT_QUOTES);

            case 'url':
                return rawurlencode($value);
        }
        return $value;
    }

    /**
     * HTTP Protocol directive
     *
     * Usage:
     *
     *     {{protocol}} - current protocol http or https
     *     {{protocol url="www.domain.com/"}} - domain URL with current protocol
     *     {{protocol http="http://url" https="https://url"}}
     *     {{protocol store="1"}} - Optional parameter which gets protocol from provide store based on store ID or code
     *
     * @param string[] $construction
     * @throws \Magento\Framework\Exception\MailException
     * @return string
     */
    public function protocolDirective($construction)
    {
        $params = $this->_getParameters($construction[2]);
        $store = null;
        if (isset($params['store'])) {
            try {
                $store = $this->_storeManager->getStore($params['store']);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\MailException(
                    __('Requested invalid store "%1"', $params['store'])
                );
            }
        }
        $isSecure = $this->_storeManager->getStore($store)->isCurrentlySecure();
        $protocol = $isSecure ? 'https' : 'http';
        if (isset($params['url'])) {
            return $protocol . '://' . $params['url'];
        } elseif (isset($params['http']) && isset($params['https'])) {
            if ($isSecure) {
                return $params['https'];
            }
            return $params['http'];
        }

        return $protocol;
    }

    /**
     * Store config directive
     *
     * @param string[] $construction
     * @return string
     */
    public function configDirective($construction)
    {
        $configValue = '';
        $params = $this->_getParameters($construction[2]);
        $storeId = $this->getStoreId();
        if (isset($params['path'])) {
            $configValue = $this->_scopeConfig->getValue(
                $params['path'],
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        return $configValue;
    }

    /**
     * Custom Variable directive
     *
     * @param string[] $construction
     * @return string
     */
    public function customvarDirective($construction)
    {
        $customVarValue = '';
        $params = $this->_getParameters($construction[2]);
        if (isset($params['code'])) {
            $variable = $this->_variableFactory->create()->setStoreId(
                $this->getStoreId()
            )->loadByCode(
                $params['code']
            );
            $mode = $this->isPlainTemplateMode()
                ? \Magento\Variable\Model\Variable::TYPE_TEXT
                : \Magento\Variable\Model\Variable::TYPE_HTML;
            $value = $variable->getValue($mode);
            if ($value) {
                $customVarValue = $value;
            }
        }
        return $customVarValue;
    }

    /**
     * Load and return the contents of a CSS file
     *
     * Usage:
     *
     *     {{css file="css/filename.css"}} - Load file from theme directory
     *     {{css file="Magento_Sales::css/filename.css"}} - Load file from module directory or module directory in theme
     *
     * @param string[] $construction
     * @return string
     */
    public function cssDirective($construction)
    {
        $params = $this->_getParameters($construction[2]);
        $file = isset($params['file']) ? $params['file'] : null;
        if (!$file) {
            // Return CSS comment for debugging purposes
            return '/* ' . __('"file" parameter must be specified') . ' */';
        }

        $css = $this->getCssFilesContent([$params['file']]);

        if (strpos($css, \Magento\Framework\Css\PreProcessor\Adapter\Oyejorge::ERROR_MESSAGE_PREFIX) !== false) {
            // Return LESS compilation error wrapped in CSS comment
            return '/*' . PHP_EOL . $css . PHP_EOL . '*/';
        } elseif (!empty($css)) {
            return $css;
        } else {
            // Return CSS comment for debugging purposes
            return '/* ' . sprintf(__('Contents of %s could not be loaded or is empty'), $file) . ' */';
        }
    }

    /**
     * Set file to apply as inline CSS
     *
     * This directive will cause CSS files to be applied inline to the HTML in the email template.
     * This directive does not inline the CSS itself, but adds the files to the parent template model so that the model
     * can handle the inlining at a later point, once all HTML has been assembled.
     *
     * Usage:
     *
     *     {{inlinecss file="css/filename.css"}} - Load file from theme directory
     *     {{inlinecss file="Magento_Sales::css/filename.css"}} - Load file from module directory or module
     *                                                            directory in theme
     *
     * @param string[] $construction
     * @return string
     * @throws \Magento\Framework\Exception\MailException
     */
    public function inlinecssDirective($construction)
    {
        // Plain text templates shouldn't have CSS applied inline
        if ($this->isPlainTemplateMode()) {
            return '';
        }

        // If this template is a child of another template, skip processing so that the parent template will process
        // this directive. This is important as CSS inlining must operate on the entire HTML document.
        if ($this->isChildTemplate()) {
            return $construction[0];
        }

        $params = $this->_getParameters($construction[2]);
        if (!isset($params['file']) || !$params['file']) {
            throw new \Magento\Framework\Exception\MailException(
                __('"file" parameter must be specified and must not be empty')
            );
        }

        $this->addInlineCssFile($params['file']);

        // CSS should be applied after entire template has been filtered, so add as after filter callback
        $this->addAfterFilterCallback([$this, 'applyInlineCss']);
        return '';
    }

    /**
     * Add filename of CSS file to inline
     *
     * @param string $file
     * @return $this
     */
    protected function addInlineCssFile($file)
    {
        $this->inlineCssFiles[] = $file;
        return $this;
    }

    /**
     * Get filename of CSS file to inline
     *
     * @return array
     */
    protected function getInlineCssFiles()
    {
        return $this->inlineCssFiles;
    }

    /**
     * Load CSS file from materialized static view directory
     *
     * @param [] $files
     * @return string
     * @throws \Magento\Framework\Exception\MailException
     */
    public function getCssFilesContent(array $files)
    {
        // Remove duplicate files
        $files = array_unique($files);

        $designParams = $this->getDesignParams();
        if (!count($designParams)) {
            throw new \Magento\Framework\Exception\MailException(
                __('Design params must be set before calling this method')
            );
        }
        $css = '';
        foreach ($files as $file) {
            $asset = $this->_assetRepo->createAsset($file, $designParams);
            $css .= $asset->getContent();
        }
        return $css;
    }

    /**
     * Merge HTML and CSS and return HTML that has CSS styles applied "inline" to the HTML tags. This is necessary
     * in order to support all email clients.
     *
     * @param string $html
     * @return string
     * @throws \Magento\Framework\Exception\MailException
     */
    public function applyInlineCss($html)
    {
        // Check to see if the {{inlinecss file=""}} directive set CSS file(s) to inline and then load those files
        $cssToInline = $this->getCssFilesContent(
            $this->getInlineCssFiles()
        );
        // Only run Emogrify if HTML and CSS contain content
        if ($html && $cssToInline) {
            try {
                // Don't try to compile CSS that has LESS compilation errors
                if (strpos($cssToInline, \Magento\Framework\Css\PreProcessor\Adapter\Oyejorge::ERROR_MESSAGE_PREFIX)
                    !== false
                ) {
                    throw new \Magento\Framework\Exception\MailException(
                        __('<pre>' . PHP_EOL . $cssToInline . PHP_EOL . '</pre>')
                    );
                }

                $emogrifier = $this->emogrifier;
                $emogrifier->setHtml($html);
                $emogrifier->setCss($cssToInline);

                // Don't parse inline <style> tags, since existing tag is intentionally for non-inline styles
                $emogrifier->disableStyleBlocksParsing();

                $processedHtml = $emogrifier->emogrify();
            } catch (\Exception $e) {
                if ($this->_appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER) {
                    $processedHtml = __('CSS inlining error:') . PHP_EOL . $e->getMessage()
                        . PHP_EOL
                        . $html;
                } else {
                    $processedHtml = $html;
                }
                $this->_logger->error($e);
            }
        } else {
            $processedHtml = $html;
        }
        return $processedHtml;
    }

    /**
     * Filter the string as template
     *
     * Overrides parent method in order to handle exceptions
     *
     * @param string $value
     * @return string
     * @SuppressWarnings(PHPMD.ConstructorWithNameAsEnclosingClass)
     */
    public function filter($value)
    {
        try {
            $value = parent::filter($value);
        } catch (\Exception $e) {
            // Since a single instance of this class can be used to filter content multiple times, reset callbacks to
            // prevent callbacks running for unrelated content (e.g., email subject and email body)
            $this->resetAfterFilterCallbacks();

            if ($this->_appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER) {
                $value = sprintf(__('Error filtering template: %s'), $e->getMessage());
            } else {
                $value = __("We're sorry, an error has occurred while generating this email.");
            }
            $this->_logger->critical($e);
        }
        return $value;
    }
}
