/**
 * Assembles an optional preview prompt (client tooling).
 * Canonical scene wording for the pipeline lives in backend `config/prompts/photo.php`
 * (`styles` + `ui_scene_lines`); authenticated users receive `photo_ui_scene_lines`
 * inside `user.credits.photo_flow`.
 */

const CONTENT_TYPE = {
  photo: 'Output: photorealistic product imagery suitable for ads and social.',
  card: 'Output: a clean e-commerce product card layout with strong typography hierarchy.',
  video: 'Output: vertical short-form video storyboard / motion concept for the product.',
}

/**
 * Fallback when `photo_ui_scene_lines` is missing (offline / stale bundle).
 * Keep in sync with backend `prompts.ui_scene_lines` in photo.php.
 */
export const DEFAULT_PHOTO_UI_SCENE_LINES = {
  from_wishes:
    'Scene: follow the user wishes as the primary art direction — composition, lighting, setting, and mood come from their description.',
  no_watermark:
    'Scene: remove watermarks, shop URLs, stickers, screenshots UI text, and overlaid typography from the reference; place the product on a clean neutral studio background; final image — no captions or added graphics beyond physical print on the product; sharp, listing-ready.',
  studio:
    'Scene: strip watermarks and all non-physical overlaid text from the reference; isolated catalog hero — clean white seamless background, soft even lighting, sharp product detail — no captions, stamps, or added graphics except molded or printed marks on the product itself.',
  environment:
    'Scene: product placed in a believable real-world environment; natural light, depth, context.',
}

/**
 * @param {object} opts
 * @param {'photo'|'card'|'video'} opts.contentType
 * @param {string} opts.sceneStyle — e.g. from_wishes, studio, no_watermark
 * @param {Record<string, string>} [opts.sceneLines] — from `photo_flow.photo_ui_scene_lines`
 * @param {string} [opts.userWishes]
 * @param {string} [opts.imageCaption]
 */
export function buildProductGenerationPrompt({
  contentType,
  sceneStyle,
  sceneLines = {},
  userWishes = '',
  imageCaption = '',
}) {
  const map = { ...DEFAULT_PHOTO_UI_SCENE_LINES, ...sceneLines }
  const sceneLine = map[sceneStyle] ?? ''

  const parts = [
    'Task: generate marketing visuals based on the uploaded product reference image.',
    CONTENT_TYPE[contentType],
    sceneLine,
  ].filter(Boolean)

  if (imageCaption.trim() && contentType !== 'photo') {
    parts.push(`Reference image description (from analysis): ${imageCaption.trim()}`)
  }
  if (userWishes.trim()) {
    parts.push(`Additional user direction: ${userWishes.trim()}`)
  }
  return parts.join('\n\n')
}
