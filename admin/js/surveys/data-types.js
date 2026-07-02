/**
 * Runtime context container
 *   The actual properties will vary depending on runtime context
 * @typedef {Object} RuntimeContext
 */

/**
 * @typedef {Object.<number, string>} IntStringMap
 */

/**
 * @typedef {*} BooleanFlag 
 * A value interpreted according to JavaScript truthiness rules.
 */

/*
 * @typedef {"section"|"group"} ContainerType
 */

/**
 * @typedef {"group"|"question"} ContentType
 */

/**
 * @typedef {"section"|"group"|"question"} ItemType
 */

/**
 * @typedef {jQuery<HTMLLIElement>} jQueryLIElement
 */

/**
 * @typedef {jQuery<HTMLULElement>} jQueryULElement
 */

/**
 * @typedef {Object} QuestionID
 * @property {number} question_id
 */

/**
 * @typedef {"INFO"|"BOOL"|"FREETEXT"|"SELECT_ONE"|"SELECT_MULTI"} QuestionType
 */

/** 
 * @typedef {"LEFT"|"RIGHT"} BoolLayout
 */

/**
 * @typedef {"ROW"|"LCOL"|"RCOL"} SelectLayout
 */

/**
 * @typedef {Object} SectionInfo
 * @property {number} section_id
 * @property {string} name
 * @property {string} intro
 * @property {BooleanFag} collapsible Boolean like value
 * @property {Array<SectionContent>} content
 */

/**
 * @typedef {Object} SectionContent
 * @property {ContainerType} type
 * @property {number} id
 */

/**
 * @typedef {Object} GroupInfo
 * @property {number} group_id
 * @property {string} name
 * @property {Array<number>} content Question IDs
 */

/**
 * @typedef {Object} QuestionInfo
 * @property {number} id
 * @property {QuestionType} type
 * @property {string} [infotag] (Info questions only)
 * @property {string} [wording] (N/A for Info questions)
 * @property {string} [info] (Info questions only)
 * @property {string} [intro] (N/A for Info questions)
 * @property {Array<number>} [options] (Select type questions only)
 * @property {BooleanFag} [other_flag] (Select type questions only)
 * @property {string} [other="Other"] (only applicable if other_flag is truthy)
 * @property {string} [qualifier] (N/A for Info questions)
 * @property {string} [popup] (N/A for Info questions)
 * @property {BooleanFag} [render_in_group] Boolean like value (Info questions only)
 * @property {null|BoolLayout|SelectLayout} [layout] 
 */

/**
 * @typedef {[jQueryLIElement,jQueryULElement]} jQueryTreeNodePair
 */

/**
 * @typedef {[HTMLLIElement,HTMLULElement]} DOMTreeNodePair
 */

/**
 * @typedef {Object} SelectedItem
 * @property {ItemType} item_type
 * @property {number} item_id
 */

/**
 * @typedef {WhereInSection|WhereRelativeToContent} WhereToAddGroup
 *e

/**
 * @typedef {WhereInSection|WhereInGroup|WhereRelativeToContent} WhereToAddQuestion
 */

/**
 * @typedef {Object} WhereInSection
 * @property {number} section_id ID of the section into which to add the item
 * @property {number} [index=0] Position within section (-1 = at end)
 */

/**
 * @typedef {Object} WhereInGroup
 * @property {number} group_id ID of the group into which to add the item
 * @property {bynber} [index=0] Position within group (-1 = at end)
 */

/**
 * @typedef {Object} WhereRelativeToSection
 * @property {number} section_id ID of the existing section used to position new section
 * @property {-1|1} offset Where to put the new section: -1=before, 1=after relative to existing section
 */

/**
 * @typedef {Object} WhereRelativeToContent
 * @property {number} ref_id ID of existing item used to position new group
 * @property {ContentType} ref_type type of the reference item
 * @property {-1|1} offset Where to put the new item: -1=before, 1=after
 */

/**
 * @typedef {Object} GroupStructure
 * @property {number} group_id
 * @property {Array<number>} content array of question IDs
 */

/**
 * @typedef {Array<QuestionID|GroupStructure>} SurveyStructure
 */

/**
 * @typedef {Object} SurveyContent
 * @property {IntStringMap} options
 * @property {Object.<number,SectionInfo>} sections
 * @property {SurveyGroups} groups
 * @property {SurveyQuestions} questions
 * @property {NextIDs} next_ids
 */

/**
 * @typedef {Object} NextIDs
 * @property {number} survey
 * @property {number} quesiton
 * @property {number} Option
 */