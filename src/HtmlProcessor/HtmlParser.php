<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\HtmlProcessor;

/**
 * Parse a HTML document fragment and add missing root elements.
 *
 * @author Kieran Brahney <kieran@supportpal.com>
 */
class HtmlParser
{
    /**
     * Structure of a basic HTML document.
     *
     * @var array
     */
    protected $tree = [
        'doctype' => '',
        'html'    => [
            'start'   => "<html>",
            'end'     => "</html>", // can't have attributes on closing html tags
            'content' => [],
        ],
        'head'    => [
            'start'   => "<head>", // can't have attributes on head tag
            'end'     => "</head>", // can't have attributes on closing head tag
            'content' => []
        ],
        'body'    => [
            'start'   => "<body>",
            'end'     => "</body>", // can't have attributes on closing body tag
            'content' => []
        ],
    ];

    /**
     * What root element did we last add to.
     *
     * @var string|null
     */
    protected $previousKey = null;

    /**
     * Parse a HTML document.
     *
     * @param  string  $html
     */
    public function loadHtml($html)
    {
        $i = 0;
        while ($i < strlen($html)) {
            if ($html[$i] == "<") {
                // Found a tag, get chars until the end of the tag.
                $tag = "";
                while ($i < strlen($html) && $html[$i] != ">") {
                    $tag .= $html[$i++];
                }

                if ($i < strlen($html) && $html[$i] == ">") {
                    $tag .= $html[$i++];

                    // Copy any whitespace following the tag.
                    // Anything added here needs to be added to the rtrim in the nodeName function.
                    while ($i < strlen($html) && preg_match('/\s/', $html[$i])) {
                        $tag .= $html[$i++];
                    }
                } else {
                    // Missing closing tag?
                    $tag .= ">";
                }

                $this->addToTree($tag);
            } else {
                $this->addToTree($html[$i++]);
            }
        }
    }

    /**
     * Format the document in a structured way (ensures root elements exists and moves scripts/css into <body>).
     *
     * @return string
     */
    public function saveHtml()
    {
        // Initialise buffer.
        $buffer = '';

        // Add <!DOCTYPE> - this is optional.
        $buffer .= $this->tree['doctype'];

        // Add <html>
        $buffer .= $this->tree['html']['start'];

        // Add head
        $buffer .= $this->tree['head']['start'];
        foreach ($this->tree['head']['content'] as $node) {
            $buffer .= $node;
        }
        $buffer .= $this->tree['head']['end'];

        // Add body
        $buffer .= $this->tree['body']['start'];
        foreach ($this->tree['body']['content'] as $node) {
            $buffer .= $node;
        }
        $buffer .= $this->tree['body']['end'];

        // Close </html> tag
        return $buffer . $this->tree['html']['end'];
    }

    /**
     * Add a node into the tree for the correct parent.
     *
     * @param  string  $node
     * @return bool
     */
    protected function addToTree($node)
    {
        if ($node[0] == "<") {
            switch (strtolower($this->nodeName($node))) {
                case "!doctype":
                    if (empty($this->tree['doctype'])) {
                        return $this->tree['doctype'] = $node;
                    }

                    // Don't overwrite if we've already got a doctype defintion.
                    return true;

                case "html":
                    return $this->addTo('html', $node, false);

                case "head":
                    return $this->addTo('head', $node);

                default:
                    return $this->addTo($this->previousKey ?? 'body', $node);
            }
        }

        // text node
        return $this->addTo($this->previousKey ?? 'body', $node);
    }

    /**
     * Add a node to the the tree.
     *
     * @param  string  $key
     * @param  string  $node
     * @param  bool  $setPrevious
     * @return bool
     */
    protected function addTo($key, $node, $setPrevious = true)
    {
        $previousKey = $key;

        if (stripos($node, "<$key") !== false) {
            $this->tree[$key]['start'] = $node;
        } elseif (stristr($node, "/$key>")) {
            $this->tree[$key]['end'] = $node;
            $previousKey = null;
        } else {
            $this->tree[$key]['content'][] = $node;
        }

        if ($setPrevious) {
            $this->previousKey = $previousKey;
        }

        return true;
    }

    /**
     * Get the name of a node without </>
     *
     * @param  string  $node
     * @return string
     */
    protected function nodeName($node)
    {
        $name = preg_replace('/>\s*/', '', ltrim($node, "</"));

        return explode(" ", $name)[0];
    }
}
