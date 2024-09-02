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
        private EntrypointRenderer $entrypointRenderer,
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

        foreach ($this->entrypointRenderer->getRenderedTags() as $tag) {
            if (!$tag->isRenderAsLinkHeader()) {
                continue;
            }

            $link = null;

            if ($tag->isStylesheet()) {
                $href = $tag->getAttribute('href');
                if (!is_string($href)) {
                    continue;
                }

                $link = $this->createLink('preload', $href)->withAttribute('as', 'style');
            } elseif ($tag->isPreload()) {
                $href = $tag->getAttribute('href');
                $rel = $tag->getAttribute('rel');
                if (!is_string($href) || !is_string($rel)) {
                    continue;
                }

                if ('modulepreload' === $rel) {
                    $link = $this->createLink('modulepreload', $href);
                } elseif ('preload' === $rel) {
                    $link = $this->createLink('preload', $href)->withAttribute('as', $tag->getAttribute('as') ?? false);
                }
            } elseif ($tag->isScriptTag()) {
                $src = $tag->getAttribute('src');
                if (!is_string($src)) {
                    continue;
                }

                if ($tag->isModule()) {
                    $link = $this->createLink('modulepreload', $src);
                } else {
                    $link = $this->createLink('preload', $src)->withAttribute('as', 'script');
                }
            }

            if (is_null($link)) {
                continue;
            }

            $crossOrigin = $tag->getAttribute('crossorigin');
            if (true === $crossOrigin || is_string($crossOrigin)) {
                $link = $link->withAttribute('crossorigin', $crossOrigin);
            }

            $linkProvider = $linkProvider->withLink($link);
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
