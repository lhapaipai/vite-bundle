<?php

namespace Pentatrion\ViteBundle\EventListener;

use Pentatrion\ViteBundle\Service\EntrypointRenderer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

class PreloadAssetsEventListener implements EventSubscriberInterface
{
    public function __construct(
        private EntrypointRenderer $entrypointRenderer
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->attributes->has('_links')) {
            $request->attributes->set(
                '_links',
                new GenericLinkProvider()
            );
        }

        /** @var GenericLinkProvider $linkProvider */
        $linkProvider = $request->attributes->get('_links');

        foreach ($this->entrypointRenderer->getRenderedScripts() as $href => $tag) {
            $rel = $tag->isModule() || $tag->isModulePreload() ? 'modulepreload' : 'preload';
            $link = $this->createLink($rel, $href)->withAttribute('as', 'script');

            $linkProvider = $linkProvider->withLink($link);
        }

        foreach ($this->entrypointRenderer->getRenderedStyles() as $filePath => $tag) {
            $href = $tag->getAttribute('href');
            if (is_string($href)) {
                $link = $this->createLink('preload', $href)->withAttribute('as', 'style');
                $linkProvider = $linkProvider->withLink($link);
            }
        }

        $request->attributes->set('_links', $linkProvider);
    }

    private function createLink(string $rel, string $href): Link
    {
        return new Link($rel, $href);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must run before AddLinkHeaderListener
            'kernel.response' => ['onKernelResponse', 50],
        ];
    }
}
