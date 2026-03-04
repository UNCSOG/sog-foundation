import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Panel, PanelBody, PanelRow, TextControl, ToggleControl } from '@wordpress/components';
import { Fragment, RawHTML } from '@wordpress/element';
import { more } from '@wordpress/icons';
// import { useServerSideRender, ServerSideRender } from '@wordpress/server-side-render';
import { default as ServerSideRender } from '@wordpress/server-side-render';
import json from '../block.json';

/**
 * Function to create the inspector controls and do the server side rendering
 *
 * @param {*} props
 * @returns <Fragment>
 */

export function Edit(props) {
	const { attributes } = props;
	const { skipLinkTarget, showSkipLink } = attributes;
	const { block, setAttributes } = props;

	const blockProps = useBlockProps(props);
	//this should work in wp >= v6.9
	// const { content, status, error } = useServerSideRender({
	// 	block: json.name
	// });
	//console.log( blockProps );
	return (
		<Fragment>
			<InspectorControls>
				<Panel>
					<PanelBody header='Skip Link Settings' initialOpen={true} icon={more}>
						<PanelRow>
							<ToggleControl
								label='Add a skip link'
								className='add-skip-link'
								checked={showSkipLink}
								_nextHasNoMarginBottom={true}
								onChange={(state) => {
									setAttributes({ showSkipLink: state });
								}}
							/>
						</PanelRow>

						{showSkipLink && (
							<PanelRow>
								<TextControl
									label='Skip Link Target'
									value={skipLinkTarget}
									_nextHasNoMarginBottom={true}
									onChange={(value) => {
										//is what the user put in a selector already?
										if (0 === value.search(/#|./)) {
											var selector = value;
										}

										if (selector) {
											setAttributes({ skipLinkTarget: selector });
										}
									}}
									type='text'
								/>
							</PanelRow>
						)}
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div {...blockProps}><ServerSideRender block={json.name} /></div>
			{/* <div {...blockProps}>
				{status === 'loading' && <div>Loading...</div>}
				{status === 'error' && <div>Error: {error}</div>}
				{(status !== 'loading' || status !== 'error') && <RawHTML>{ content }</RawHTML> }
			</div> */}
		</Fragment>
	);
}
