<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A interface for social media services used for getting counts
 */
interface SocialServiceInterface {
    public function processQueue();
}
