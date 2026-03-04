/**
 * Alert Service Banner
 *
 * from
 * https://wp-gb.com/contrastchecker/
 * https://github.com/WordPress/gutenberg/tree/trunk/packages/block-editor/src/components/contrast-checker
 * https://awhitepixel.com/blog/how-to-add-color-settings-to-your-custom-gutenberg-block/
 * https://github.com/WordPress/gutenberg/issues/31223
 *
 */

import { Panel, PanelBody, PanelRow, ToggleControl } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';
//wp v <= 6.8
import { default as ServerSideRender } from '@wordpress/server-side-render';
import json from '../block.json';

/**
 * Color Settings Block Componant
 *
 * @param {*} props
 * @returns <Fragment>
 */
const EditBlock = (props) => {
	const {
		attributes: { hiddenInEditor, useRest, preview },
		setAttributes,
	} = props;

	var extraProps = {};
	if (document.body.classList.contains('block-editor-page') && hiddenInEditor) {
		extraProps = {
			className: 'd-none',
		};
	}
	const blockProps = useBlockProps(extraProps);
	return (
		<Fragment>
			<InspectorControls>
				<Panel>
					<PanelBody title='Alert Settings' initialOpen={true}>
						<PanelRow>
							<ToggleControl
								label='Use Rest API'
								help={
									'If checked, will pull the alert from the rest api rather then rendering it.  if the rest api is unavailable it will read from the rave cap feed'
								}
								checked={useRest}
								onChange={(newValue) => {
									setAttributes({ useRest: newValue });
								}}
							/>
						</PanelRow>
						<PanelRow>
							<ToggleControl
								label='Hidden In Editor'
								help={'Show and hide the alert in the editor'}
								checked={hiddenInEditor}
								onChange={(newValue) => {
									setAttributes({ hiddenInEditor: newValue });
								}}
							/>
						</PanelRow>
					</PanelBody>
				</Panel>
			</InspectorControls>

			<div {...blockProps}>
				<ServerSideRender
					block={json.name}
					attributes={{
						preview: preview,
						hiddenInEditor: hiddenInEditor,
					}}
				/>
			</div>
		</Fragment>
	);
};

export { EditBlock };
