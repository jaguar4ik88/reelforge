<?php

/*
 * ─────────────────────────────────────────────────────────────
 * ReelForge — шаблонные промпты для генерации через Kontext
 *
 * Структура каждого шаблона:
 *   scene_prompt        — описывает всё что есть на фото шаблона
 *                         (фон, свет, окружение, композиция)
 *   kontext_instruction — говорит модели что именно заменить
 *                         и что сохранить без изменений
 *
 * Финальный промпт = scene_prompt + " " + kontext_instruction
 *
 * Типы съёмки:
 *   studio     — студийное фото товара
 *   background — товар на фоне / в окружении
 *   in_use     — товар в использовании (на человеке / в руках)
 *   card       — карточка товара (чистая композиция, текст добавляется программно)
 * ─────────────────────────────────────────────────────────────
 */

return [

    /*
    |──────────────────────────────────────────────────────────────
    | 1. ОБУВЬ (shoes)
    |──────────────────────────────────────────────────────────────
    */
    'shoes' => [

        'studio' => [
            'scene_prompt' => 'A single pair of shoes placed on a pure white seamless studio background. Professional three-point softbox lighting, perfectly even exposure, no shadows on background. Camera angle: 45-degree side view, slightly elevated. Tack-sharp focus across the entire shoe. Clean, minimal, classic e-commerce setup.',
            'kontext_instruction' => 'Replace the shoes in the scene with the product from the reference image. Keep the camera angle, lighting setup, background, and composition exactly the same. Preserve all studio lighting and white background.',
        ],

        'background' => [
            'scene_prompt' => 'A pair of shoes placed on weathered wooden boards in an urban outdoor setting. Brick wall in the background, slightly blurred. Golden hour sunlight coming from the left side, casting a long warm shadow to the right. Shallow depth of field f/2.0. Street photography editorial mood, warm tones.',
            'kontext_instruction' => 'Replace the shoes in the scene with the product from the reference image. Keep the wooden surface, brick wall background, golden hour lighting, shadow direction, and overall composition exactly as they are. Only the shoes should change.',
        ],

        'in_use' => [
            'scene_prompt' => 'A person walking on a clean urban sidewalk, shot from the knees down. The person is wearing casual slim-fit jeans. Shoes are clearly visible and in motion — one foot slightly raised mid-step. Natural daylight, slightly overcast, soft shadows. Street style photography, 50mm lens, realistic and authentic feel.',
            'kontext_instruction' => 'Replace only the footwear on the person\'s feet with the product from the reference image. Keep the legs, jeans, walking pose, sidewalk surface, lighting, and all surroundings exactly the same. The new shoes must look naturally worn on the feet.',
        ],

        'card' => [
            'scene_prompt' => 'A single pair of shoes centered on a very light grey gradient background, positioned at a clean 3/4 angle. Soft diffused overhead light, subtle drop shadow underneath the shoes. Generous empty space on the left side and top for text overlay. Minimal, premium e-commerce product card composition.',
            'kontext_instruction' => 'Replace the shoes in the scene with the product from the reference image. Keep the light grey background, centered composition, 3/4 camera angle, soft lighting, and empty space areas for text. Only the product changes.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 2. ОДЕЖДА (clothing)
    |──────────────────────────────────────────────────────────────
    */
    'clothing' => [

        'studio' => [
            'scene_prompt' => 'A clothing item displayed on an invisible mannequin on a pure white seamless background. Professional studio softbox lighting from both sides, even and shadow-free. The garment is perfectly pressed and shaped. Shot straight-on at chest height. Clean catalog presentation.',
            'kontext_instruction' => 'Replace the clothing item on the mannequin with the product from the reference image. Keep the invisible mannequin shape, white background, studio lighting, straight-on camera angle, and clean composition exactly the same.',
        ],

        'background' => [
            'scene_prompt' => 'A clothing item hanging on a minimalist black metal rack against a warm off-white textured plaster wall. Soft natural light from a large window to the left. A few complementary garments slightly blurred in the background. Editorial lifestyle photography, 35mm lens, airy and aspirational mood.',
            'kontext_instruction' => 'Replace the main clothing item on the rack with the product from the reference image. Keep the metal rack, plaster wall background, natural window lighting, blurred background garments, and overall composition unchanged. Only the featured product changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'A person standing in a bright modern interior space, facing slightly away from camera at a 3/4 angle. The clothing is the clear focus of the shot. The person has a relaxed confident posture, arms slightly away from body to show garment shape. Soft natural light. Fashion editorial style, full body shot.',
            'kontext_instruction' => 'Replace only the clothing worn by the person with the product from the reference image. Keep the person\'s pose, body position, facial expression, background interior, lighting, and camera angle exactly the same. The garment must look naturally worn.',
        ],

        'card' => [
            'scene_prompt' => 'A clothing item neatly folded and placed on a smooth light cream surface, shot from directly above (flat lay). Soft even diffused lighting, no harsh shadows. Clean empty cream background visible around the garment. Generous space at the top and right side for text overlay. Minimal and elegant.',
            'kontext_instruction' => 'Replace the folded clothing item with the product from the reference image, maintaining the same flat lay folded presentation. Keep the cream surface, overhead camera angle, soft lighting, and empty space for text exactly as they are.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 3. СУМКИ / АКСЕССУАРЫ (bags)
    |──────────────────────────────────────────────────────────────
    */
    'bags' => [

        'studio' => [
            'scene_prompt' => 'A handbag standing upright on a pure white seamless surface. Professional studio lighting with a key light from upper left and soft fill from the right. The bag is shaped naturally with its handles up. Shot at a 45-degree angle, slightly elevated camera. Sharp focus on the entire bag including texture details. Classic luxury product photography.',
            'kontext_instruction' => 'Replace the handbag in the scene with the product from the reference image. Keep the white seamless background, upright bag position, studio lighting setup, camera angle, and sharp detail focus exactly the same. Only the bag changes.',
        ],

        'background' => [
            'scene_prompt' => 'A handbag placed on a rustic wooden cafe table near a window. Blurred Parisian street scene visible through the window in the background. A glass of coffee partially visible to the side, slightly out of focus. Warm afternoon natural light. Lifestyle fashion photography, 50mm lens, aspirational everyday mood.',
            'kontext_instruction' => 'Replace the handbag on the cafe table with the product from the reference image. Keep the wooden table, window, blurred street background, coffee glass prop, warm natural lighting, and composition exactly the same. Only the bag changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'A woman carrying a handbag over her shoulder, walking on a city street. Shot from the side at waist level. The bag is clearly visible, hanging naturally at hip height. The person is wearing a neutral-toned outfit. Natural daylight, city background blurred. Authentic street style editorial photography.',
            'kontext_instruction' => 'Replace only the handbag being carried with the product from the reference image. Keep the person\'s arm, shoulder, outfit, walking pose, city street background, lighting, and camera angle exactly the same. The bag must appear to hang naturally from the shoulder.',
        ],

        'card' => [
            'scene_prompt' => 'A handbag centered on a soft white marble surface, shot at a clean 3/4 front angle. Gentle diffused studio light from above, soft shadow underneath. The bag handles are neatly arranged. Plenty of empty space on the right side and above for text overlay. Premium e-commerce card composition.',
            'kontext_instruction' => 'Replace the handbag with the product from the reference image. Keep the marble surface, 3/4 camera angle, soft overhead lighting, shadow, handle arrangement style, and empty text space areas exactly as they are. Only the bag changes.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 4. ЭЛЕКТРОНИКА / ГАДЖЕТЫ (electronics)
    |──────────────────────────────────────────────────────────────
    */
    'electronics' => [

        'studio' => [
            'scene_prompt' => 'A tech device placed on a pure matte black surface against a very dark charcoal seamless background. Slim dramatic rim lighting from behind highlighting the product edges. A subtle cool blue reflection on the surface below. Shot straight-on at slight elevation. Sharp focus on every detail. Premium tech product photography.',
            'kontext_instruction' => 'Replace the tech device in the scene with the product from the reference image. Keep the matte black surface, dark background, rim lighting, blue surface reflection, camera angle, and overall dramatic composition exactly the same. Only the device changes.',
        ],

        'background' => [
            'scene_prompt' => 'A tech gadget placed on a clean minimal wooden desk. A closed notebook and a ceramic coffee mug are visible in the background, slightly blurred. Soft natural daylight from the left side window. Warm productive workspace atmosphere. 35mm lens, lifestyle tech photography.',
            'kontext_instruction' => 'Replace the gadget on the desk with the product from the reference image. Keep the wooden desk surface, notebook and mug props, window light direction, warm atmosphere, depth of field, and composition exactly the same. Only the featured product changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'A person\'s hands holding a smartphone or small device at a comfortable reading angle. Shot from slightly above, showing hands and device clearly. Person is seated at a light-colored desk, casual setting. Clean natural light. Hands are relaxed and natural. Authentic everyday usage feel.',
            'kontext_instruction' => 'Replace only the device being held in the hands with the product from the reference image. Keep the hands, holding position, finger placement, desk surface, lighting, camera angle, and all surroundings exactly the same. The product must appear naturally held.',
        ],

        'card' => [
            'scene_prompt' => 'A tech device centered on a pure white background, shot perfectly straight-on. Clean symmetrical composition. Soft even studio lighting, no reflections on screen if present. Generous empty space above and to the right for text overlay. Precise, clinical, modern e-commerce presentation.',
            'kontext_instruction' => 'Replace the device with the product from the reference image. Keep the white background, straight-on camera angle, symmetrical centering, clean lighting, and empty space for text exactly as they are. Only the product changes.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 5. КОСМЕТИКА / ПАРФЮМ (cosmetics)
    |──────────────────────────────────────────────────────────────
    */
    'cosmetics' => [

        'studio' => [
            'scene_prompt' => 'A cosmetic product bottle or package standing upright on a smooth white surface. Soft diffused overhead studio light, gentle shadow underneath. Shot at a slight 15-degree angle from front. The label and packaging details are clearly visible and sharp. Clean, bright, pharmaceutical-grade product photography.',
            'kontext_instruction' => 'Replace the cosmetic product with the product from the reference image. Keep the white surface, upright position, diffused studio lighting, shadow, slight angle, and sharp packaging detail focus exactly the same. Only the product changes.',
        ],

        'background' => [
            'scene_prompt' => 'A beauty product placed among fresh white flowers and green leaves on a light pink surface. Small water droplets on nearby petals. Soft natural window light from above left. Pastel, fresh, organic beauty brand aesthetic. 90mm lens, shallow depth of field, flowers slightly blurred.',
            'kontext_instruction' => 'Replace the beauty product with the product from the reference image. Keep the flowers, green leaves, water droplets, pink surface, soft natural lighting, depth of field, and overall floral composition exactly the same. Only the product changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'A close-up shot of a woman\'s hand applying a cream or serum to the back of her other hand. Both hands are visible and well-groomed. Clean neutral background, soft studio lighting. The product container is visible and open next to the hands. Beauty tutorial photography style, authentic and clean.',
            'kontext_instruction' => 'Replace only the cosmetic product container visible in the scene with the product from the reference image. Keep the hands, application gesture, skin, neutral background, lighting, and composition exactly the same. The product must appear naturally placed in the scene.',
        ],

        'card' => [
            'scene_prompt' => 'A cosmetic product centered on a soft pink pastel background. Shot slightly from above at a clean angle. Minimal composition with one or two small pearl beads as decorative props. Gentle even studio lighting. Space on the left side and top for text overlay. Feminine, premium beauty brand presentation.',
            'kontext_instruction' => 'Replace the cosmetic product with the product from the reference image. Keep the pink background, camera angle, pearl prop decorations, soft lighting, and text space areas exactly as they are. Only the product changes.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 6. ЕДА / НАПИТКИ (food)
    |──────────────────────────────────────────────────────────────
    */
    'food' => [

        'studio' => [
            'scene_prompt' => 'A food or beverage product package standing on a pure white seamless surface. Bright even studio lighting from three sides, no shadows on background. The packaging label faces camera directly. Shot straight-on at label height. Every packaging detail is crisp and readable. Clean commercial food photography.',
            'kontext_instruction' => 'Replace the food or beverage product with the product from the reference image. Keep the white seamless background, label-forward orientation, bright even lighting, straight-on camera angle, and clean composition exactly the same. Only the product changes.',
        ],

        'background' => [
            'scene_prompt' => 'A beverage or food product placed on a rustic wooden table outdoors. Bright summer daylight, green nature blurred in the background. Fresh fruits or ingredients naturally scattered nearby as props. Warm vibrant colors. Food lifestyle photography, 50mm lens, fresh and appetizing mood.',
            'kontext_instruction' => 'Replace the food or beverage product with the product from the reference image. Keep the wooden table, outdoor background, daylight, fruit props, warm color palette, depth of field, and composition exactly the same. Only the product changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'A person\'s hand holding a beverage bottle or food package at a natural comfortable angle. Shot from the side slightly above. Casual outdoor setting, blurred greenery in background. Natural sunlight. The product label is clearly visible facing camera. Authentic lifestyle moment.',
            'kontext_instruction' => 'Replace only the product being held in the hand with the product from the reference image. Keep the hand, grip position, finger placement, outdoor background, lighting, label visibility angle, and composition exactly the same. The product must appear naturally held.',
        ],

        'card' => [
            'scene_prompt' => 'A food or beverage product centered on a clean white background. Shot straight-on so the label faces camera completely. Even bright studio lighting, no shadows. Generous empty space on the right half of the frame for text overlay. Simple, clear, supermarket-ready product card composition.',
            'kontext_instruction' => 'Replace the product with the product from the reference image. Keep the white background, straight-on label-facing camera angle, bright even lighting, and large empty space on the right for text exactly as they are. Only the product changes.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 7. ЮВЕЛИРНЫЕ УКРАШЕНИЯ (jewelry)
    |──────────────────────────────────────────────────────────────
    */
    'jewelry' => [

        'studio' => [
            'scene_prompt' => 'A jewelry piece placed on a dark navy velvet surface. Single focused spotlight from upper right creating strong specular highlights on the metal and gemstones. Deep dark background fading to black. Shot close up at a slight elevation angle. Every facet and detail razor sharp. Luxury jewelry store photography.',
            'kontext_instruction' => 'Replace the jewelry piece with the product from the reference image. Keep the dark velvet surface, spotlight direction, specular highlights style, dark background, close-up camera angle, and sharp detail focus exactly the same. Only the jewelry changes.',
        ],

        'background' => [
            'scene_prompt' => 'A piece of jewelry resting on white rose petals scattered on a light marble surface. Soft diffused natural light from above. A few petals slightly blurred in foreground for depth. Elegant, romantic, editorial feel. 85mm macro lens, very shallow depth of field.',
            'kontext_instruction' => 'Replace the jewelry piece with the product from the reference image. Keep the rose petals, marble surface, blurred foreground petals, soft natural lighting, shallow depth of field, and romantic composition exactly the same. Only the jewelry changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'A close-up of a woman\'s hand and wrist wearing a bracelet or ring. Elegant well-groomed hand with neutral nail polish. Shot from slightly above at a 3/4 angle. Soft studio light, neutral light grey background. The jewelry is the clear focus, sharp and detailed. Luxury accessories photography.',
            'kontext_instruction' => 'Replace only the jewelry worn on the hand or wrist with the product from the reference image. Keep the hand, skin tone, nail polish, pose, grey background, lighting, and camera angle exactly the same. The jewelry must appear naturally worn.',
        ],

        'card' => [
            'scene_prompt' => 'A jewelry piece centered on a pure white background, shot directly from above (overhead flat lay). Even bright diffused studio lighting, no harsh reflections. The piece is arranged to show its full shape. Plenty of empty space on all sides, especially the right half, for text overlay. Clean luxury presentation.',
            'kontext_instruction' => 'Replace the jewelry piece with the product from the reference image. Keep the white background, overhead camera angle, centered arrangement, bright even lighting, and empty space for text exactly as they are. Only the jewelry changes.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 8. ТОВАРЫ ДЛЯ ДОМА (home)
    |──────────────────────────────────────────────────────────────
    */
    'home' => [

        'studio' => [
            'scene_prompt' => 'A home product placed on a light grey seamless surface. Clean professional studio lighting from upper left, soft shadow on the right. Shot at a 45-degree angle showing the front and one side of the product. All details, materials, and textures are crisp and clear. Neutral, clean catalog photography.',
            'kontext_instruction' => 'Replace the home product with the product from the reference image. Keep the grey seamless surface, studio lighting direction, soft shadow, 45-degree camera angle, and clean composition exactly the same. Only the product changes.',
        ],

        'background' => [
            'scene_prompt' => 'A home product placed in a real living room setting. Bright airy Scandinavian interior, white walls, light oak wooden floor. A few complementary home decor items subtly visible in the background, blurred. Soft natural light from a large window to the left. Interior lifestyle photography, warm and inviting.',
            'kontext_instruction' => 'Replace the home product with the product from the reference image. Keep the Scandinavian interior setting, white walls, wooden floor, background decor props, natural window light, and warm inviting atmosphere exactly the same. Only the featured product changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'A person\'s hands using or holding a home product in a natural kitchen or living room context. The hands are interacting with the product in its intended way. Warm natural interior light. The background shows a blurred but recognizable home environment. Authentic lifestyle usage photography.',
            'kontext_instruction' => 'Replace only the home product being used or held with the product from the reference image. Keep the hands, usage gesture, home interior background, lighting, and composition exactly the same. The product must appear naturally integrated into the usage scenario.',
        ],

        'card' => [
            'scene_prompt' => 'A home product centered on a clean white background. Shot at a clear 3/4 front angle showing the product\'s best side. Soft even studio lighting with a subtle shadow base. Empty space on the right side and top third of the frame for text overlay. Modern e-commerce product card layout.',
            'kontext_instruction' => 'Replace the home product with the product from the reference image. Keep the white background, 3/4 camera angle, soft even lighting, shadow base, and empty space for text exactly as they are. Only the product changes.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 9. СПОРТИВНЫЕ ТОВАРЫ (sports)
    |──────────────────────────────────────────────────────────────
    */
    'sports' => [

        'studio' => [
            'scene_prompt' => 'A sports product placed on a dark anthracite surface. Strong dramatic side lighting from the left with a cool rim light from behind. Deep dark background. Dynamic product angle showing shape and design. High contrast, energetic, sports brand aesthetic. Sharp focus on every surface detail.',
            'kontext_instruction' => 'Replace the sports product with the product from the reference image. Keep the dark surface, dramatic side lighting, cool rim light, dark background, dynamic product angle, and high-contrast energetic composition exactly the same. Only the product changes.',
        ],

        'background' => [
            'scene_prompt' => 'A sports product placed on asphalt in an outdoor sports court or track environment. Bold graphic painted lines on the ground visible around the product. Bright midday daylight, strong sharp shadows. Vibrant energetic atmosphere. Wide angle lens, low camera angle looking slightly up. Dynamic outdoor sports photography.',
            'kontext_instruction' => 'Replace the sports product with the product from the reference image. Keep the asphalt surface, painted court lines, bright midday lighting, strong shadows, low camera angle, and energetic outdoor atmosphere exactly the same. Only the product changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'An athlete in motion using a sports product in an outdoor sports environment. The person is mid-action — running, jumping, or exercising. Shot with a fast shutter speed freezing the motion. Natural bright outdoor light. The sports product is clearly visible and central to the action. Dynamic sports photography.',
            'kontext_instruction' => 'Replace only the sports product being used by the athlete with the product from the reference image. Keep the athlete\'s body, motion, athletic outfit, outdoor sports environment, lighting, and dynamic composition exactly the same. The product must appear naturally integrated into the action.',
        ],

        'card' => [
            'scene_prompt' => 'A sports product on a pure white background, positioned at a dynamic diagonal angle suggesting movement and energy. Strong even studio lighting, crisp sharp shadows. The product angle shows its best design features. Space in the upper left corner and right side for text overlay. Energetic yet clean product card.',
            'kontext_instruction' => 'Replace the sports product with the product from the reference image. Keep the white background, diagonal dynamic angle, strong studio lighting, sharp shadows, and text space areas exactly as they are. Only the product changes.',
        ],
    ],

    /*
    |──────────────────────────────────────────────────────────────
    | 10. ДРУГОЕ / УНИВЕРСАЛЬНОЕ (other)
    |──────────────────────────────────────────────────────────────
    */
    'other' => [

        'studio' => [
            'scene_prompt' => 'A product placed on a neutral light grey seamless surface. Balanced professional three-point studio lighting, soft and even from all sides. No harsh shadows. Shot at a clean 45-degree angle showing front and one side. All product details, materials, and finishes are clearly visible. Universal commercial product photography.',
            'kontext_instruction' => 'Replace the product in the scene with the product from the reference image. Keep the grey seamless surface, balanced studio lighting, clean 45-degree camera angle, and overall neutral commercial composition exactly the same. Only the product changes.',
        ],

        'background' => [
            'scene_prompt' => 'A product placed on a natural wooden surface with a soft blurred neutral background. Gentle natural light from the left side. A few minimal lifestyle props subtly visible but out of focus. Clean, warm, and inviting atmosphere. 50mm lens, shallow depth of field. Universal lifestyle product photography.',
            'kontext_instruction' => 'Replace the product with the product from the reference image. Keep the wooden surface, blurred neutral background, natural side lighting, minimal props, warm atmosphere, and depth of field exactly the same. Only the product changes.',
        ],

        'in_use' => [
            'scene_prompt' => 'A person\'s hands holding or interacting with a product in a natural everyday context. The hands and product are the clear focus of the shot. Clean neutral background, soft natural light. The interaction looks authentic and relaxed. Shot from slightly above at a comfortable viewing angle.',
            'kontext_instruction' => 'Replace only the product being held or used with the product from the reference image. Keep the hands, interaction gesture, neutral background, lighting, camera angle, and composition exactly the same. The product must appear naturally held and integrated.',
        ],

        'card' => [
            'scene_prompt' => 'A product centered on a pure white background. Shot straight-on or at a slight 3/4 angle showing the product clearly. Clean even studio lighting with a very soft shadow base. Generous empty space on the right half of the frame and at the top for text overlay. Simple, clear, universal e-commerce card composition.',
            'kontext_instruction' => 'Replace the product with the product from the reference image. Keep the white background, camera angle, clean studio lighting, soft shadow base, and generous empty space for text exactly as they are. Only the product changes.',
        ],
    ],

];
