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
  in_use:
    'Scene: product in real use — hands or context showing application; authentic lifestyle feel.',
  environment:
    'Scene: product placed in a believable real-world environment; natural light, depth, context.',
  studio:
    'Scene: catalog / studio shot — isolated product on neutral background, soft even lighting, sharp detail.',
}

/**
 * @param {object} opts
 * @param {'photo'|'card'|'video'} opts.contentType
 * @param {'in_use'|'environment'|'studio'} opts.sceneStyle
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
