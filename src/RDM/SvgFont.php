<?php
namespace RDM;

use DOMNode;
use DOMDocument;

class SvgFont
{

    private $id;

    private $unicodes;

    private $glyphs;

    public function __construct(DOMNode $fontElement)
    {
        $this->id = $fontElement->getAttribute('id');
        
        // Iterate over each glyph and store it in an array for fast access
        foreach ($fontElement->getElementsByTagName('glyph') as $glyphNode) {
            // Store each glyph id in an array using the glyph name as the key
            $this->unicodes[$glyphNode->getAttribute('glyph-name')] = $glyphNode->getAttribute('unicode');
            
            // Store data for each glyph in an array using the unicode value as the key
            $this->glyphs[$glyphNode->getAttribute('unicode')] = (object) [
                'path' => $glyphNode->getAttribute('d'),
                'advx' => $glyphNode->hasAttribute('horiz-adv-x') ? $glyphNode->getAttribute('horiz-adv-x') : $fontElement->getAttribute('horiz-adv-x')
            ];
        }
    }

    public function generateSvg($text)
    {
        $document = new DOMDocument();
        $group = $document->createElement('g');
        $advance = 0;
        
        foreach (preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY) as $char) {
            $glyph = $this->glyphs[$char];
            
            $path = $document->createElement('path');
            $path->setAttribute('d', $glyph->path);
            $path->setAttribute('fill-rule', 'evenodd');
            $path->setAttribute('transform', 'translate(' . $advance . ', 1000) scale(1, -1)');
            
            $advance += $glyph->advx;
            
            $group->appendChild($path);
        }
        
        return $group;
    }
}
