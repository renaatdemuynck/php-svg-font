<?php
namespace RDM;

use DOMNode;
use DOMDocument;

class SvgFont
{

    private $font;

    public function __construct(DOMNode $fontElement)
    {
        $this->font['id'] = $fontElement->getAttribute('id');
        
        foreach ($fontElement->getElementsByTagName('glyph') as $glyphNode) {
            $this->font['glyphs'][$glyphNode->getAttribute('unicode')] = [
                'name' => $glyphNode->getAttribute('glyph-name'),
                'path' => $glyphNode->getAttribute('d'),
                'advx' => $glyphNode->getAttribute('horiz-adv-x')
            ];
        }
    }

    public function generateSvg($text)
    {
        $document = new DOMDocument();
        $group = $document->createElement('g');
        $advance = 0;
        
        foreach (preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY) as $char) {
            $glyph = $this->font['glyphs'][$char];
            
            $path = $document->createElement('path');
            $path->setAttribute('d', $glyph['path']);
            $path->setAttribute('fill-rule', 'evenodd');
            $path->setAttribute('transform', 'translate(' . $advance . ', 1000) scale(1, -1)');
            
            $advance += $glyph['advx'];
            
            $group->appendChild($path);
        }
        
        return $group;
    }
}
