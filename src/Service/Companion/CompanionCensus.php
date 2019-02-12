<?php

namespace App\Service\Companion;

/**
 * Generate all stats about items, including cross-world summaries, chart data,
 * hq/nq sale amounts, the lot!
 */
class CompanionCensus
{
    /** @var \stdClass */
    private $market;
    
    public function generate($market): self
    {
        
        
        //die('ok');
        return $this;
    }
}
