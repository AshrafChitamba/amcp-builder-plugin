/**
 * Turns a shared video link into something the Tutorial screen can play.
 */
export type VideoEmbed =
	| { kind: 'iframe'; src: string; provider: string }
	| { kind: 'file'; src: string };

const YOUTUBE_ID = /^[A-Za-z0-9_-]{11}$/;

export const videoEmbed = ( raw: string ): VideoEmbed | null => {
	let url: URL;
	try {
		url = new URL( raw.trim() );
	} catch {
		return null;
	}
	if ( url.protocol !== 'https:' ) {
		return null;
	}

	const host = url.hostname.replace( /^(www\.|m\.)/, '' );
	const parts = url.pathname.split( '/' ).filter( Boolean );

	if ( host === 'youtu.be' || host.endsWith( 'youtube.com' ) ) {
		let id: string | null | undefined =
			host === 'youtu.be' ? parts[ 0 ] : url.searchParams.get( 'v' );
		if (
			! id &&
			[ 'embed', 'shorts', 'live' ].includes( parts[ 0 ] ?? '' )
		) {
			id = parts[ 1 ];
		}
		return id && YOUTUBE_ID.test( id )
			? {
					kind: 'iframe',
					provider: 'YouTube',
					src: `https://www.youtube-nocookie.com/embed/${ id }?rel=0`,
				}
			: null;
	}

	if ( host === 'vimeo.com' || host === 'player.vimeo.com' ) {
		const id = parts.find( ( part ) => /^\d+$/.test( part ) );
		return id
			? {
					kind: 'iframe',
					provider: 'Vimeo',
					src: `https://player.vimeo.com/video/${ id }`,
				}
			: null;
	}

	if ( host === 'loom.com' && [ 'share', 'embed' ].includes( parts[ 0 ] ) ) {
		return parts[ 1 ]
			? {
					kind: 'iframe',
					provider: 'Loom',
					src: `https://www.loom.com/embed/${ parts[ 1 ] }`,
				}
			: null;
	}

	if ( host === 'drive.google.com' ) {
		const id =
			parts[ 0 ] === 'file' && parts[ 1 ] === 'd'
				? parts[ 2 ]
				: url.searchParams.get( 'id' );
		return id
			? {
					kind: 'iframe',
					provider: 'Google Drive',
					src: `https://drive.google.com/file/d/${ id }/preview`,
				}
			: null;
	}

	if ( /\.(mp4|webm|mov)$/i.test( url.pathname ) ) {
		return { kind: 'file', src: url.toString() };
	}

	return null;
};
