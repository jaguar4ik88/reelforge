/**
 * Assembles a text prompt from structured UI choices + optional vision caption.
 * Vision analysis should be performed on the backend and passed as imageCaption.
 */

const CONTENT_TYPE = {
  photo: 'Output: photorealistic product imagery suitable for ads and social.',
  card:  'Output: a clean e-commerce product card layout with strong typography hierarchy.',
  video: 'Output: vertical short-form video storyboard / motion concept for the product.',
}

const SCENE_STYLE = {
  from_wishes:
    'Scene: follow the user wishes as the primary art direction — composition, lighting, setting, and mood come from their description.',
  no_watermark:
    'Scene: remove watermarks, shop URLs, and overlaid text from the reference; place the product on a clean neutral studio background, sharp and listing-ready.',
  environment:
    'Scene: product placed in a believable real-world environment; natural light, depth, context.',
  studio:
    'Scene: catalog / studio shot — isolated product on neutral background, soft even lighting, sharp detail.',
}

/**
 * @param {object} opts
 * @param {'photo'|'card'|'video'} opts.contentType
 * @param {'no_watermark'|'environment'|'studio'} opts.sceneStyle
 * @param {string} [opts.userWishes]
 * @param {string} [opts.imageCaption] — from vision / captioning API (server-side)
 */
export function buildProductGenerationPrompt({
  contentType,
  sceneStyle,
  userWishes = '',
  imageCaption = '',
}) {
  const parts = [
    'Task: generate marketing visuals based on the uploaded product reference image.',
    CONTENT_TYPE[contentType],
    SCENE_STYLE[sceneStyle],
  ]
  if (imageCaption.trim()) {
    parts.push(`Reference image description (from analysis): ${imageCaption.trim()}`)
  }
  if (userWishes.trim()) {
    parts.push(`Additional user direction: ${userWishes.trim()}`)
  }
  return parts.join('\n\n')
}
