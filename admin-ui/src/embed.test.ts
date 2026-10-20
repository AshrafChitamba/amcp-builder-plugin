import { describe, expect, it } from 'vitest';

import { videoEmbed } from './embed';

const src = ( url: string ) => {
	const embed = videoEmbed( url );
	return embed?.src ?? null;
};

describe( 'videoEmbed', () => {
	it( 'reads every common YouTube link form', () => {
		const expected =
			'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0';
		expect( src( 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ) ).toBe(
			expected
		);
		expect( src( 'https://youtu.be/dQw4w9WgXcQ?si=abc' ) ).toBe( expected );
		expect( src( 'https://youtube.com/shorts/dQw4w9WgXcQ' ) ).toBe(
			expected
		);
		expect( src( 'https://m.youtube.com/watch?v=dQw4w9WgXcQ&t=10' ) ).toBe(
			expected
		);
	} );

	it( 'reads Vimeo, Loom and Google Drive links', () => {
		expect( src( 'https://vimeo.com/76979871' ) ).toBe(
			'https://player.vimeo.com/video/76979871'
		);
		expect( src( 'https://www.loom.com/share/abc123def' ) ).toBe(
			'https://www.loom.com/embed/abc123def'
		);
		expect(
			src( 'https://drive.google.com/file/d/1AbC_dEf/view?usp=sharing' )
		).toBe( 'https://drive.google.com/file/d/1AbC_dEf/preview' );
		expect( src( 'https://drive.google.com/open?id=1AbC_dEf' ) ).toBe(
			'https://drive.google.com/file/d/1AbC_dEf/preview'
		);
	} );

	it( 'plays direct video files', () => {
		expect( videoEmbed( 'https://cdn.example.com/tour.mp4' ) ).toEqual( {
			kind: 'file',
			src: 'https://cdn.example.com/tour.mp4',
		} );
	} );

	it( 'rejects unknown, malformed and insecure links', () => {
		expect( videoEmbed( '' ) ).toBeNull();
		expect( videoEmbed( 'not a url' ) ).toBeNull();
		expect( videoEmbed( 'http://youtu.be/dQw4w9WgXcQ' ) ).toBeNull();
		expect( videoEmbed( 'https://example.com/page' ) ).toBeNull();
		expect( videoEmbed( 'https://youtube.com/watch?v=short' ) ).toBeNull();
	} );
} );
