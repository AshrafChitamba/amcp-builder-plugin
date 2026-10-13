import { useEffect, useState } from '@wordpress/element';

import { errorMessage, getSettings, saveSettings } from '../api';
import type { Settings } from '../types';

/**
 * Loads plugin settings and saves partial updates.
 */
export const useSettings = () => {
	const [ settings, setSettings ] = useState< Settings | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ saving, setSaving ] = useState( false );

	useEffect( () => {
		getSettings()
			.then( setSettings )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [] );

	const save = async ( patch: Partial< Settings > ): Promise< boolean > => {
		setSaving( true );
		setError( null );
		try {
			setSettings( await saveSettings( patch ) );
			return true;
		} catch ( e ) {
			setError( errorMessage( e ) );
			return false;
		} finally {
			setSaving( false );
		}
	};

	return {
		settings,
		save,
		saving,
		error,
		clearError: () => setError( null ),
	};
};
